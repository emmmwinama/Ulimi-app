<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CropFieldRepository;
use App\Repositories\FieldRepository;
use App\Repositories\TransactionRepository;

final class ApiFinanceController
{
    public function __construct(
        private readonly TransactionRepository $tx = new TransactionRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $filters = array_filter([
            'type' => (string) $request->query('type', ''),
            'category' => (string) $request->query('category', ''),
            'season' => (string) $request->query('season', ''),
            'crop_field_id' => (string) $request->query('crop_field_id', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ]);
        return Response::json(['data' => $this->tx->forFarm($ctx->farmId(), $filters)]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $data = $this->prepare($request, $ctx->farmId());
        if ($data instanceof Response) {
            return $data;
        }

        $id = $this->tx->create($ctx->farmId(), (string) ApiAuth::id(), $data);
        AuditLog::user('api.transaction.created', (string) ApiAuth::id(), ['tx_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->tx->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        $existing = $this->tx->find($ctx->farmId(), $id);
        if ($existing === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }
        if (($existing['source'] ?? 'manual') !== 'manual') {
            return Response::json(['error' => 'This transaction was generated from an inventory sale and can’t be edited directly.'], 422);
        }

        $data = $this->prepare($request, $ctx->farmId());
        if ($data instanceof Response) {
            return $data;
        }

        $this->tx->update($ctx->farmId(), $id, $data);
        AuditLog::user('api.transaction.updated', (string) ApiAuth::id(), ['tx_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->tx->find($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->tx->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $this->tx->delete($ctx->farmId(), $id);
        AuditLog::user('api.transaction.deleted', (string) ApiAuth::id(), ['tx_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /** @return array<string,mixed>|Response */
    private function prepare(Request $request, string $farmId): array|Response
    {
        $v = Validator::make($request->all(), [
            'type' => ['required', 'in:Income,Expense'],
            'category' => ['required', 'max:80'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'date' => ['required', 'date'],
            'description' => ['required', 'max:255'],
            'season' => ['max:60'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $fieldId = (string) $request->input('field_id', '');
        if ($fieldId !== '' && $this->fields->find($farmId, $fieldId) === null) {
            $fieldId = '';
        }
        $cropFieldId = (string) $request->input('crop_field_id', '');
        if ($cropFieldId !== '' && $this->crops->find($farmId, $cropFieldId) === null) {
            $cropFieldId = '';
        }

        return [
            'type' => (string) $d['type'],
            'category' => trim((string) $d['category']),
            'payment_status' => (string) $d['payment_status'],
            'amount' => (float) $d['amount'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'description' => trim((string) $d['description']),
            'season' => isset($d['season']) && $d['season'] !== '' ? trim((string) $d['season']) : null,
            'field_id' => $fieldId ?: null,
            'crop_field_id' => $cropFieldId ?: null,
            // Never trust a client-supplied source — this must always read
            // 'manual' so the inventory-sale-generated invariant (checked in
            // update() above) can never be forged from the API.
            'source' => 'manual',
        ];
    }
}
