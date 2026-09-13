<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\MarketPriceRepository;
use App\Repositories\MarketPriceUpdateRepository;
use App\Services\MarketPriceFeed;
use App\Support\Dates;

final class AdminMarketController extends Controller
{
    public function __construct(
        private readonly MarketPriceRepository $prices = new MarketPriceRepository(),
        private readonly MarketPriceUpdateRepository $pendingUpdates = new MarketPriceUpdateRepository(),
        private readonly MarketPriceFeed $feed = new MarketPriceFeed(),
    ) {
    }

    public function index(Request $request): Response
    {
        $this->feed->checkForUpdates();

        $pending = array_map(function (array $p): array {
            $p['current_price_avg'] = $p['existing_price_id'] !== null
                ? ($this->prices->find((string) $p['existing_price_id'])['price_avg'] ?? null)
                : null;
            return $p;
        }, $this->pendingUpdates->pending());

        return $this->view('admin/market/index', [
            'title' => 'Market data', 'active' => 'market',
            'rows' => $this->prices->all(),
            'pending' => $pending,
        ]);
    }

    public function checkNow(Request $request): Response
    {
        $found = $this->feed->checkForUpdates(force: true);
        if ($found < 0) {
            Flash::error('Could not reach the price feed. Try again later.');
        } else {
            Flash::success($found > 0 ? "Found {$found} price update" . ($found === 1 ? '' : 's') . ' to review.' : 'No new price changes found.');
        }
        return $this->redirect(url('admin/market'));
    }

    public function approveUpdate(Request $request): Response
    {
        $id = (string) $request->route('id');
        $pending = $this->pendingUpdates->find($id);
        if ($pending === null) {
            Flash::error('Update not found.');
            return $this->redirect(url('admin/market'));
        }

        $row = [
            'crop_name' => $pending['crop_name'],
            'variety' => $pending['variety'],
            'unit' => $pending['unit'],
            'price_min' => $pending['price_min'],
            'price_max' => $pending['price_max'],
            'price_avg' => $pending['price_avg'],
            'market' => $pending['market'],
            'region' => $pending['region'],
            'currency' => $pending['currency'],
            'season' => null,
            'recorded_at' => $pending['recorded_at'],
            'source' => $pending['source'],
            'is_active' => 1,
        ];

        if ($pending['existing_price_id'] !== null && $this->prices->find((string) $pending['existing_price_id']) !== null) {
            $this->prices->update((string) $pending['existing_price_id'], $row);
            $priceId = (string) $pending['existing_price_id'];
        } else {
            $priceId = $this->prices->create($row);
        }

        $this->pendingUpdates->delete($id);
        AuditLog::admin('admin.market_price_approved', (string) AdminAuth::id(), 'market_price', $priceId, [
            'crop_name' => $pending['crop_name'], 'region' => $pending['region'], 'price_avg' => $pending['price_avg'],
        ], $request->ip());
        Flash::success('Price update applied.');
        return $this->redirect(url('admin/market'));
    }

    public function rejectUpdate(Request $request): Response
    {
        $id = (string) $request->route('id');
        $pending = $this->pendingUpdates->find($id);
        if ($pending === null) {
            Flash::error('Update not found.');
            return $this->redirect(url('admin/market'));
        }

        $this->pendingUpdates->delete($id);
        AuditLog::admin('admin.market_price_rejected', (string) AdminAuth::id(), 'market_price_update', $id, [
            'crop_name' => $pending['crop_name'], 'region' => $pending['region'],
        ], $request->ip());
        Flash::success('Update dismissed.');
        return $this->redirect(url('admin/market'));
    }

    public function store(Request $request): Response
    {
        $data = $this->prepared($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->prices->create($data);
        AuditLog::admin('admin.market_price_created', (string) AdminAuth::id(), 'market_price', $id, [], $request->ip());
        Flash::success('Price added.');
        return $this->redirect(url('admin/market'));
    }

    public function update(Request $request): Response
    {
        $id = (string) $request->route('id');
        if ($this->prices->find($id) === null) {
            Flash::error('Not found.');
            return $this->redirect(url('admin/market'));
        }
        $data = $this->prepared($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->prices->update($id, $data);
        AuditLog::admin('admin.market_price_updated', (string) AdminAuth::id(), 'market_price', $id, [], $request->ip());
        Flash::success('Price updated.');
        return $this->redirect(url('admin/market'));
    }

    public function destroy(Request $request): Response
    {
        $id = (string) $request->route('id');
        $this->prices->delete($id);
        AuditLog::admin('admin.market_price_deleted', (string) AdminAuth::id(), 'market_price', $id, [], $request->ip());
        Flash::success('Price removed.');
        return $this->redirect(url('admin/market'));
    }

    /** @return array<string,mixed>|Response */
    private function prepared(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'crop_name' => ['required', 'max:120'],
            'variety'   => ['max:120'],
            'unit'      => ['required', 'max:20'],
            'price_min' => ['required', 'numeric', 'min:0'],
            'price_max' => ['required', 'numeric', 'min:0'],
            'price_avg' => ['required', 'numeric', 'min:0'],
            'market'    => ['required', 'max:120'],
            'region'    => ['required', 'max:120'],
            'currency'  => ['required', 'max:3'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        return [
            'crop_name' => trim((string) $data['crop_name']),
            'variety'   => ($data['variety'] ?? '') !== '' ? trim((string) $data['variety']) : null,
            'unit'      => trim((string) $data['unit']),
            'price_min' => (float) $data['price_min'],
            'price_max' => (float) $data['price_max'],
            'price_avg' => (float) $data['price_avg'],
            'market'    => trim((string) $data['market']),
            'region'    => trim((string) $data['region']),
            'currency'  => strtoupper((string) $data['currency']),
            'season'    => null,
            'recorded_at' => Dates::nowUtc(),
            'source'    => 'ADMARC',
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ];
    }
}
