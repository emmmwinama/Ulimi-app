<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\ActivityRepository;
use App\Repositories\BuyerRepository;
use App\Repositories\CropFieldRepository;
use App\Repositories\CropIncidentRepository;
use App\Repositories\EquipmentRepository;
use App\Repositories\FieldRepository;
use App\Repositories\HarvestYieldRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\LivestockRepository;
use App\Repositories\TransactionRepository;
use RuntimeException;
use Throwable;

/**
 * Batched offline-queue processor for the mobile app's local drafts. Each
 * item is validated and created independently — one bad item doesn't fail
 * the whole batch. The response lists a result per item, keyed by the
 * client's own `client_id` (a locally-generated id the app uses to
 * reconcile which drafts synced), so the client can safely retry only what
 * failed.
 *
 * Create-only, deliberately: a record made offline always becomes a new
 * server record. Edits/deletes of existing records happen online, through
 * the regular per-resource PUT/DELETE endpoints, once the device has a
 * connection — never through this batch queue.
 */
final class ApiSyncController
{
    private const MAX_ITEMS_PER_BATCH = 50;

    private const LIVESTOCK_EVENT_TABLES = [
        'health' => 'animal_health',
        'production' => 'animal_production',
        'weight' => 'animal_weight',
        'expense' => 'animal_expenses',
    ];

    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly TransactionRepository $tx = new TransactionRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly CropIncidentRepository $incidents = new CropIncidentRepository(),
        private readonly LivestockRepository $livestock = new LivestockRepository(),
        private readonly InventoryRepository $inventory = new InventoryRepository(),
        private readonly BuyerRepository $buyers = new BuyerRepository(),
        private readonly EquipmentRepository $equipment = new EquipmentRepository(),
        private readonly HarvestYieldRepository $yields = new HarvestYieldRepository(),
    ) {
    }

    public function sync(Request $request): Response
    {
        $ctx = FarmContext::current();
        $userId = (string) ApiAuth::id();

        $batches = [
            'activities' => (array) $request->input('activities', []),
            'transactions' => (array) $request->input('transactions', []),
            'crop_incidents' => (array) $request->input('crop_incidents', []),
            'livestock_health' => (array) $request->input('livestock_health', []),
            'livestock_weight' => (array) $request->input('livestock_weight', []),
            'livestock_production' => (array) $request->input('livestock_production', []),
            'livestock_expenses' => (array) $request->input('livestock_expenses', []),
            'inventory_sales' => (array) $request->input('inventory_sales', []),
            'equipment_logs' => (array) $request->input('equipment_logs', []),
            'harvest_yields' => (array) $request->input('harvest_yields', []),
        ];

        $totalItems = array_sum(array_map('count', $batches));
        if ($totalItems > self::MAX_ITEMS_PER_BATCH) {
            return Response::json(['error' => 'Batch too large. Send at most ' . self::MAX_ITEMS_PER_BATCH . ' items.'], 413);
        }

        $results = array_fill_keys(array_keys($batches), []);

        if ($ctx->can('activities.manage') && !$ctx->isReadOnly()) {
            foreach ($batches['activities'] as $item) {
                $results['activities'][] = $this->syncActivity($ctx, $userId, (array) $item);
            }
        }
        if ($ctx->can('finance.manage') && !$ctx->isReadOnly()) {
            foreach ($batches['transactions'] as $item) {
                $results['transactions'][] = $this->syncTransaction($ctx, $userId, (array) $item);
            }
        }
        if ($ctx->can('crops.manage') && !$ctx->isReadOnly()) {
            foreach ($batches['crop_incidents'] as $item) {
                $results['crop_incidents'][] = $this->syncIncident($ctx, $userId, (array) $item);
            }
        }
        if ($ctx->can('livestock.manage') && !$ctx->isReadOnly()) {
            foreach (['health', 'weight', 'production', 'expense'] as $kind) {
                $key = $kind === 'expense' ? 'livestock_expenses' : 'livestock_' . $kind;
                foreach ($batches[$key] as $item) {
                    $results[$key][] = $this->syncLivestockEvent($ctx, $kind, (array) $item);
                }
            }
        }
        if ($ctx->can('inventory.manage') && !$ctx->isReadOnly()) {
            foreach ($batches['inventory_sales'] as $item) {
                $results['inventory_sales'][] = $this->syncInventorySale($ctx, $userId, (array) $item);
            }
        }
        if ($ctx->can('equipment.manage') && !$ctx->isReadOnly()) {
            foreach ($batches['equipment_logs'] as $item) {
                $results['equipment_logs'][] = $this->syncEquipmentLog($ctx, (array) $item);
            }
        }
        if ($ctx->can('yields.manage') && !$ctx->isReadOnly()) {
            foreach ($batches['harvest_yields'] as $item) {
                $results['harvest_yields'][] = $this->syncHarvestYield($ctx, (array) $item);
            }
        }

        AuditLog::user('api.sync', $userId, array_map('count', $batches), $ctx->farmId(), $request->ip());

        return Response::json(['results' => $results]);
    }

    /** @param array<string,mixed> $item */
    private function syncActivity(FarmContext $ctx, string $userId, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        try {
            $v = Validator::make($item, [
                'field_id' => ['required'],
                'activity_type' => ['required', 'max:80'],
                'date' => ['required', 'date'],
            ]);
            if ($v->fails()) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => $v->errors()];
            }
            $d = $v->validated();
            if ($this->fields->find($ctx->farmId(), (string) $d['field_id']) === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['field_id' => ['Unknown field.']]];
            }

            $id = $this->activities->create($ctx->farmId(), $userId, [
                'field_id' => (string) $d['field_id'],
                'crop_field_id' => ($item['crop_field_id'] ?? '') !== '' ? (string) $item['crop_field_id'] : null,
                'activity_type' => (string) $d['activity_type'],
                'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
                'notes' => $item['notes'] ?? null,
                'responsible_person_name' => $item['responsible'] ?? null,
                'responsible_employee_id' => null,
            ], [], [], []);

            return ['client_id' => $clientId, 'ok' => true, 'id' => $id];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }

    /** @param array<string,mixed> $item */
    private function syncTransaction(FarmContext $ctx, string $userId, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        try {
            $v = Validator::make($item, [
                'type' => ['required', 'in:Income,Expense'],
                'category' => ['required', 'max:80'],
                'amount' => ['required', 'numeric', 'min:0'],
                'date' => ['required', 'date'],
                'description' => ['required', 'max:255'],
            ]);
            if ($v->fails()) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => $v->errors()];
            }
            $d = $v->validated();

            $id = $this->tx->create($ctx->farmId(), $userId, [
                'type' => (string) $d['type'],
                'category' => (string) $d['category'],
                'amount' => (float) $d['amount'],
                'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
                'description' => (string) $d['description'],
                'source' => 'manual',
            ]);

            return ['client_id' => $clientId, 'ok' => true, 'id' => $id];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }

    /** @param array<string,mixed> $item */
    private function syncIncident(FarmContext $ctx, string $userId, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        try {
            $v = Validator::make($item, [
                'crop_field_id' => ['required'],
                'type' => ['required', 'in:pest,disease,other'],
                'description' => ['required', 'max:500'],
                'severity' => ['required', 'in:mild,moderate,severe'],
                'reported_date' => ['required', 'date'],
                'treatment_notes' => ['max:1000'],
                'referred_to' => ['max:160'],
            ]);
            if ($v->fails()) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => $v->errors()];
            }
            $d = $v->validated();
            if ($this->crops->find($ctx->farmId(), (string) $d['crop_field_id']) === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['crop_field_id' => ['Unknown crop planting.']]];
            }

            $id = $this->incidents->create($ctx->farmId(), $userId, [
                'crop_field_id' => (string) $d['crop_field_id'],
                'type' => (string) $d['type'],
                'description' => trim((string) $d['description']),
                'severity' => (string) $d['severity'],
                'reported_date' => date('Y-m-d', (int) strtotime((string) $d['reported_date'])),
                'treatment_notes' => trim((string) ($d['treatment_notes'] ?? '')) ?: null,
                'referred_to' => trim((string) ($d['referred_to'] ?? '')) ?: null,
                'status' => 'open',
            ]);

            return ['client_id' => $clientId, 'ok' => true, 'id' => $id];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }

    /** @param array<string,mixed> $item */
    private function syncLivestockEvent(FarmContext $ctx, string $kind, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        $animalId = (string) ($item['animal_id'] ?? '');
        try {
            if ($animalId === '' || $this->livestock->findAnimal($ctx->farmId(), $animalId) === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['animal_id' => ['Unknown animal.']]];
            }

            if (!isset(self::LIVESTOCK_EVENT_TABLES[$kind])) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Unknown event kind.']]];
            }

            $row = match ($kind) {
                'health' => $this->prepareHealthEvent($item),
                'production' => $this->prepareProductionEvent($item),
                'weight' => $this->prepareWeightEvent($item),
                'expense' => $this->prepareExpenseEvent($item),
            };
            if ($row === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Invalid event data.']]];
            }

            $id = $this->livestock->addEvent(self::LIVESTOCK_EVENT_TABLES[$kind], $ctx->farmId(), $animalId, $row);

            if ($kind === 'weight') {
                $this->livestock->updateAnimal($ctx->farmId(), $animalId, ['weight' => $row['weight']]);
            }

            return ['client_id' => $clientId, 'ok' => true, 'id' => $id];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }

    /** @param array<string,mixed> $item @return array<string,mixed>|null */
    private function prepareHealthEvent(array $item): ?array
    {
        $v = Validator::make($item, [
            'type' => ['required', 'max:60'],
            'description' => ['required', 'max:255'],
            'veterinarian' => ['max:120'],
            'cost' => ['numeric', 'min:0', 'max:100000000'],
            'date' => ['required', 'date'],
            'next_due_date' => ['date'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return null;
        }
        $d = $v->validated();
        return [
            'type' => (string) $d['type'],
            'description' => trim((string) $d['description']),
            'veterinarian' => trim((string) ($d['veterinarian'] ?? '')) ?: null,
            'cost' => isset($d['cost']) && $d['cost'] !== '' ? (float) $d['cost'] : null,
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'next_due_date' => !empty($d['next_due_date']) ? date('Y-m-d', (int) strtotime((string) $d['next_due_date'])) : null,
            'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
        ];
    }

    /** @param array<string,mixed> $item @return array<string,mixed>|null */
    private function prepareProductionEvent(array $item): ?array
    {
        $v = Validator::make($item, [
            'type' => ['required', 'max:60'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'unit' => ['required', 'max:20'],
            'date' => ['required', 'date'],
            'price_per_unit' => ['numeric', 'min:0', 'max:100000000'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return null;
        }
        $d = $v->validated();
        $qty = (float) $d['quantity'];
        $price = isset($d['price_per_unit']) && $d['price_per_unit'] !== '' ? (float) $d['price_per_unit'] : null;
        return [
            'type' => (string) $d['type'],
            'quantity' => $qty,
            'unit' => (string) $d['unit'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'price_per_unit' => $price,
            'total_value' => $price !== null ? round($qty * $price, 2) : null,
            'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
        ];
    }

    /** @param array<string,mixed> $item @return array<string,mixed>|null */
    private function prepareWeightEvent(array $item): ?array
    {
        $v = Validator::make($item, [
            'weight' => ['required', 'numeric', 'min:0', 'max:100000'],
            'unit' => ['required', 'in:kg,lb'],
            'date' => ['required', 'date'],
            'notes' => ['max:300'],
        ]);
        if ($v->fails()) {
            return null;
        }
        $d = $v->validated();
        return [
            'weight' => (float) $d['weight'],
            'unit' => (string) $d['unit'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
        ];
    }

    /** @param array<string,mixed> $item @return array<string,mixed>|null */
    private function prepareExpenseEvent(array $item): ?array
    {
        $v = Validator::make($item, [
            'category' => ['required', 'max:60'],
            'description' => ['required', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'date' => ['required', 'date'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return null;
        }
        $d = $v->validated();
        return [
            'category' => (string) $d['category'],
            'description' => trim((string) $d['description']),
            'amount' => (float) $d['amount'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
        ];
    }

    /** @param array<string,mixed> $item */
    private function syncInventorySale(FarmContext $ctx, string $userId, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        try {
            $v = Validator::make($item, [
                'inventory_item_id' => ['required'],
                'quantity_sold' => ['required', 'numeric', 'min:0.001', 'max:100000000'],
                'price_per_unit' => ['required', 'numeric', 'min:0', 'max:100000000'],
                'sale_date' => ['required', 'date'],
                'buyer_id' => [],
                'buyer_name' => ['max:160'],
                'collection_point' => ['max:160'],
                'transport_method' => ['max:80'],
                'pickup_date' => ['date'],
                'notes' => ['max:500'],
            ]);
            if ($v->fails()) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => $v->errors()];
            }
            $d = $v->validated();

            $itemRow = $this->inventory->find($ctx->farmId(), (string) $d['inventory_item_id']);
            if ($itemRow === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['inventory_item_id' => ['Unknown inventory item.']]];
            }

            $qty = (float) $d['quantity_sold'];
            if ($qty > (float) $itemRow['quantity'] + 1e-9) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['quantity_sold' => ['Not enough stock — refresh and try again.']]];
            }

            $buyerId = (string) ($d['buyer_id'] ?? '');
            $buyer = $buyerId !== '' ? $this->buyers->find($ctx->farmId(), $buyerId) : null;
            $buyerName = ($d['buyer_name'] ?? '') !== '' ? trim((string) $d['buyer_name']) : ($buyer['name'] ?? null);
            $price = (float) $d['price_per_unit'];
            $total = round($qty * $price, 2);
            $saleDate = date('Y-m-d', (int) strtotime((string) $d['sale_date']));

            $saleId = Database::instance()->transaction(function () use ($ctx, $userId, $itemRow, $qty, $price, $total, $saleDate, $d, $buyer, $buyerName): string {
                if (!$this->inventory->decrement($ctx->farmId(), (string) $itemRow['id'], $qty)) {
                    throw new RuntimeException('insufficient stock');
                }

                $txId = $this->tx->create($ctx->farmId(), $userId, [
                    'type' => 'Income',
                    'category' => $itemRow['category'] === 'crop_harvest' ? 'Crop sales' : 'Other income',
                    'amount' => $total,
                    'date' => $saleDate,
                    'description' => 'Sale: ' . $itemRow['name'] . ' — ' . rtrim(rtrim((string) $qty, '0'), '.') . ' ' . $itemRow['unit']
                        . ($buyerName !== null ? ' to ' . $buyerName : ''),
                    'season' => $itemRow['season'],
                    'crop_field_id' => $itemRow['crop_field_id'],
                    'harvest_yield_id' => $itemRow['harvest_yield_id'],
                    'inventory_item_id' => (string) $itemRow['id'],
                    'source' => 'inventory_sale',
                ]);

                return $this->inventory->recordSale($ctx->farmId(), [
                    'inventory_item_id' => (string) $itemRow['id'],
                    'transaction_id' => $txId,
                    'quantity_sold' => $qty,
                    'unit' => (string) $itemRow['unit'],
                    'price_per_unit' => $price,
                    'total_amount' => $total,
                    'buyer_name' => $buyerName,
                    'buyer_id' => $buyer['id'] ?? null,
                    'collection_point' => ($d['collection_point'] ?? '') !== '' ? trim((string) $d['collection_point']) : null,
                    'transport_method' => ($d['transport_method'] ?? '') !== '' ? trim((string) $d['transport_method']) : null,
                    'pickup_date' => ($d['pickup_date'] ?? '') !== '' ? date('Y-m-d', (int) strtotime((string) $d['pickup_date'])) : null,
                    'sale_date' => $saleDate,
                    'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
                ]);
            });

            return ['client_id' => $clientId, 'ok' => true, 'id' => $saleId];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this sale.']]];
        }
    }

    /** @param array<string,mixed> $item */
    private function syncEquipmentLog(FarmContext $ctx, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        $equipmentId = (string) ($item['equipment_id'] ?? '');
        try {
            if ($equipmentId === '' || $this->equipment->find($ctx->farmId(), $equipmentId) === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['equipment_id' => ['Unknown equipment.']]];
            }

            $v = Validator::make($item, [
                'date' => ['required', 'date'],
                'description' => ['required', 'max:255'],
                'cost' => ['numeric', 'min:0', 'max:100000000'],
                'hours_used' => ['numeric', 'min:0', 'max:1000000'],
                'notes' => ['max:500'],
            ]);
            if ($v->fails()) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => $v->errors()];
            }
            $d = $v->validated();

            $id = $this->equipment->addLog($ctx->farmId(), $equipmentId, [
                'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
                'description' => trim((string) $d['description']),
                'cost' => isset($d['cost']) && $d['cost'] !== '' ? (float) $d['cost'] : null,
                'hours_used' => isset($d['hours_used']) && $d['hours_used'] !== '' ? (float) $d['hours_used'] : null,
                'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
            ]);

            return ['client_id' => $clientId, 'ok' => true, 'id' => $id];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }

    /** @param array<string,mixed> $item */
    private function syncHarvestYield(FarmContext $ctx, array $item): array
    {
        $clientId = (string) ($item['client_id'] ?? '');
        try {
            $v = Validator::make($item, [
                'crop_field_id' => ['required'],
                'harvest_date' => ['required', 'date'],
                'quantity' => ['required', 'numeric', 'min:0', 'max:100000000'],
                'unit' => ['required', 'in:kg,bags,tonnes,crates'],
                'unit_weight' => ['numeric', 'min:0', 'max:100000'],
                'notes' => ['max:500'],
            ]);
            if ($v->fails()) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => $v->errors()];
            }
            $d = $v->validated();
            if ($this->crops->find($ctx->farmId(), (string) $d['crop_field_id']) === null) {
                return ['client_id' => $clientId, 'ok' => false, 'errors' => ['crop_field_id' => ['Unknown crop planting.']]];
            }

            $id = $this->yields->create($ctx->farmId(), [
                'crop_field_id' => (string) $d['crop_field_id'],
                'harvest_date' => date('Y-m-d', (int) strtotime((string) $d['harvest_date'])),
                'quantity' => (float) $d['quantity'],
                'unit' => (string) $d['unit'],
                'unit_weight' => isset($d['unit_weight']) && $d['unit_weight'] !== '' ? (float) $d['unit_weight'] : null,
                'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
            ]);

            return ['client_id' => $clientId, 'ok' => true, 'id' => $id];
        } catch (Throwable) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }
}
