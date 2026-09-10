<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\InventoryRepository;
use App\Repositories\TransactionRepository;

final class InventoryController extends Controller
{
    private const CATEGORIES = ['crop_harvest', 'seed', 'fertiliser', 'chemical', 'equipment', 'other'];

    public function __construct(
        private readonly InventoryRepository $inventory = new InventoryRepository(),
        private readonly TransactionRepository $tx = new TransactionRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $category = (string) $request->query('category', '');
        return $this->view('inventory/index', [
            'title'      => 'Inventory',
            'active'     => 'inventory',
            'items'      => $this->inventory->forFarm($ctx->farmId(), $category ?: null),
            'categories' => self::CATEGORIES,
            'category'   => $category,
            'canManage'  => $ctx->can('inventory.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('inventory/form', [
            'title'      => 'Add stock item',
            'active'     => 'inventory',
            'item'       => null,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->inventory->create($ctx->farmId(), $data);
        AuditLog::user('inventory.created', (string) Auth::id(), ['item_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Stock item added.');
        return $this->redirect(url('inventory'));
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $item = $this->inventory->find($ctx->farmId(), (string) $request->route('id'));
        if ($item === null) {
            Flash::error('Item not found.');
            return $this->redirect(url('inventory'));
        }
        return $this->view('inventory/form', [
            'title'      => 'Edit stock item',
            'active'     => 'inventory',
            'item'       => $item,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->inventory->find($ctx->farmId(), $id) === null) {
            Flash::error('Item not found.');
            return $this->redirect(url('inventory'));
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->inventory->update($ctx->farmId(), $id, $data);
        AuditLog::user('inventory.updated', (string) Auth::id(), ['item_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Stock item updated.');
        return $this->redirect(url('inventory'));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->inventory->find($ctx->farmId(), $id) === null) {
            Flash::error('Item not found.');
            return $this->redirect(url('inventory'));
        }
        $this->inventory->delete($ctx->farmId(), $id);
        AuditLog::user('inventory.deleted', (string) Auth::id(), ['item_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Stock item deleted.');
        return $this->redirect(url('inventory'));
    }

    /* ------------------------------------------------------------------ sell */

    public function sellForm(Request $request): Response
    {
        $ctx = FarmContext::current();
        $item = $this->inventory->find($ctx->farmId(), (string) $request->route('id'));
        if ($item === null) {
            Flash::error('Item not found.');
            return $this->redirect(url('inventory'));
        }
        return $this->view('inventory/sell', [
            'title'  => 'Sell — ' . (string) $item['name'],
            'active' => 'inventory',
            'item'   => $item,
            'sales'  => $this->inventory->salesForItem($ctx->farmId(), (string) $item['id']),
        ]);
    }

    public function sell(Request $request): Response
    {
        $ctx = FarmContext::current();
        $item = $this->inventory->find($ctx->farmId(), (string) $request->route('id'));
        if ($item === null) {
            Flash::error('Item not found.');
            return $this->redirect(url('inventory'));
        }

        $data = $this->validate($request, [
            'quantity_sold'  => ['required', 'numeric', 'min:0.001', 'max:100000000'],
            'price_per_unit' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'sale_date'      => ['required', 'date'],
            'buyer_name'     => ['max:160'],
            'notes'          => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $qty = (float) $data['quantity_sold'];
        if ($qty > (float) $item['quantity'] + 1e-9) {
            return $this->fieldError($request, 'quantity_sold', 'You only have ' . rtrim(rtrim((string) $item['quantity'], '0'), '.') . ' ' . $item['unit'] . ' in stock.');
        }

        $price = (float) $data['price_per_unit'];
        $total = round($qty * $price, 2);
        $saleDate = date('Y-m-d', (int) strtotime((string) $data['sale_date']));

        try {
            Database::instance()->transaction(function () use ($ctx, $item, $qty, $price, $total, $saleDate, $data): void {
                if (!$this->inventory->decrement($ctx->farmId(), (string) $item['id'], $qty)) {
                    throw new \RuntimeException('insufficient stock');
                }

                $txId = $this->tx->create($ctx->farmId(), (string) Auth::id(), [
                    'type'              => 'Income',
                    'category'          => $item['category'] === 'crop_harvest' ? 'Crop sales' : 'Other income',
                    'amount'            => $total,
                    'date'              => $saleDate,
                    'description'       => 'Sale: ' . $item['name'] . ' — ' . rtrim(rtrim((string) $qty, '0'), '.') . ' ' . $item['unit']
                                           . (($data['buyer_name'] ?? '') !== '' ? ' to ' . trim((string) $data['buyer_name']) : ''),
                    'season'            => $item['season'],
                    'crop_field_id'     => $item['crop_field_id'],
                    'harvest_yield_id'  => $item['harvest_yield_id'],
                    'inventory_item_id' => (string) $item['id'],
                    'source'            => 'inventory_sale',
                ]);

                $this->inventory->recordSale($ctx->farmId(), [
                    'inventory_item_id' => (string) $item['id'],
                    'transaction_id'    => $txId,
                    'quantity_sold'     => $qty,
                    'unit'              => (string) $item['unit'],
                    'price_per_unit'    => $price,
                    'total_amount'      => $total,
                    'buyer_name'        => ($data['buyer_name'] ?? '') !== '' ? trim((string) $data['buyer_name']) : null,
                    'sale_date'         => $saleDate,
                    'notes'             => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
                ]);
            });
        } catch (\Throwable) {
            return $this->fieldError($request, 'quantity_sold', 'That sale would oversell your stock. Refresh and try again.');
        }

        AuditLog::user('inventory.sold', (string) Auth::id(), ['item_id' => $item['id'], 'qty' => $qty, 'total' => $total], $ctx->farmId(), $request->ip());
        Flash::success('Sale recorded — stock reduced and an income transaction was created.');
        return $this->redirect(url('inventory/' . rawurlencode((string) $item['id']) . '/sell'));
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'name'                  => ['required', 'max:160'],
            'category'              => ['required', 'in:' . implode(',', self::CATEGORIES)],
            'unit'                  => ['required', 'max:20'],
            'quantity'              => ['required', 'numeric', 'min:0', 'max:100000000'],
            'acquisition_unit_cost' => ['numeric', 'min:0', 'max:100000000'],
            'acquired_at'           => ['date'],
            'unit_weight'           => ['numeric', 'min:0', 'max:100000'],
            'season'                => ['max:60'],
            'notes'                 => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        return [
            'name'                  => trim((string) $data['name']),
            'category'              => (string) $data['category'],
            'unit'                  => trim((string) $data['unit']),
            'quantity'              => (float) $data['quantity'],
            'acquisition_unit_cost' => isset($data['acquisition_unit_cost']) && $data['acquisition_unit_cost'] !== '' ? (float) $data['acquisition_unit_cost'] : null,
            'acquired_at'           => isset($data['acquired_at']) && $data['acquired_at'] !== '' ? date('Y-m-d', (int) strtotime((string) $data['acquired_at'])) : null,
            'unit_weight'           => isset($data['unit_weight']) && $data['unit_weight'] !== '' ? (float) $data['unit_weight'] : null,
            'season'                => isset($data['season']) && $data['season'] !== '' ? trim((string) $data['season']) : null,
            'notes'                 => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ];
    }
}
