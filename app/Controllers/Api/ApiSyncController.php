<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\ActivityRepository;
use App\Repositories\FieldRepository;
use App\Repositories\TransactionRepository;
use Throwable;

/**
 * Batched offline-queue processor for the mobile app's local drafts. Each
 * item in `activities` / `transactions` is validated and created
 * independently — one bad item doesn't fail the whole batch. The response
 * lists a result per item, keyed by the client's own `client_id` (a
 * locally-generated id the app uses to reconcile which drafts synced), so
 * the client can safely retry only what failed.
 */
final class ApiSyncController
{
    private const MAX_ITEMS_PER_BATCH = 50;

    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly TransactionRepository $tx = new TransactionRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
    ) {
    }

    public function sync(Request $request): Response
    {
        $ctx = FarmContext::current();
        $userId = (string) ApiAuth::id();

        $activityItems = (array) $request->input('activities', []);
        $transactionItems = (array) $request->input('transactions', []);

        if (count($activityItems) + count($transactionItems) > self::MAX_ITEMS_PER_BATCH) {
            return Response::json(['error' => 'Batch too large. Send at most ' . self::MAX_ITEMS_PER_BATCH . ' items.'], 413);
        }

        $results = ['activities' => [], 'transactions' => []];

        if ($ctx->can('activities.manage') && !$ctx->isReadOnly()) {
            foreach ($activityItems as $item) {
                $results['activities'][] = $this->syncActivity($ctx, $userId, (array) $item);
            }
        }
        if ($ctx->can('finance.manage') && !$ctx->isReadOnly()) {
            foreach ($transactionItems as $item) {
                $results['transactions'][] = $this->syncTransaction($ctx, $userId, (array) $item);
            }
        }

        AuditLog::user('api.sync', $userId, [
            'activities' => count($activityItems), 'transactions' => count($transactionItems),
        ], $ctx->farmId(), $request->ip());

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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            return ['client_id' => $clientId, 'ok' => false, 'errors' => ['general' => ['Could not save this record.']]];
        }
    }
}
