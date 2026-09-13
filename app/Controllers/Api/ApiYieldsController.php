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
use App\Repositories\HarvestYieldRepository;
use App\Repositories\ProduceStorageRepository;

final class ApiYieldsController
{
    private const UNITS = ['kg', 'bags', 'tonnes', 'crates'];
    private const GRADES = ['A', 'B', 'C', 'reject'];

    public function __construct(
        private readonly HarvestYieldRepository $yields = new HarvestYieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly ProduceStorageRepository $storage = new ProduceStorageRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $farmId = FarmContext::current()->farmId();
        $cropFieldId = $request->query('crop_field_id');
        return Response::json(['data' => $this->yields->forFarm($farmId, $cropFieldId !== null ? (string) $cropFieldId : null)]);
    }

    public function show(Request $request): Response
    {
        $row = $this->yields->find(FarmContext::current()->farmId(), (string) $request->route('id'));
        if ($row === null) {
            return Response::json(['error' => 'Yield record not found.'], 404);
        }
        return Response::json(['data' => $row]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('yields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $data = $this->validated($request, true);
        if ($data instanceof Response) {
            return $data;
        }

        $id = $this->yields->create($ctx->farmId(), $data);
        AuditLog::user('api.yield.created', (string) ApiAuth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->yields->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('yields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->yields->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Yield record not found.'], 404);
        }

        $data = $this->validated($request, false);
        if ($data instanceof Response) {
            return $data;
        }

        $this->yields->update($ctx->farmId(), $id, $data);
        AuditLog::user('api.yield.updated', (string) ApiAuth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->yields->find($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('yields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->yields->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Yield record not found.'], 404);
        }

        $this->yields->delete($ctx->farmId(), $id);
        AuditLog::user('api.yield.deleted', (string) ApiAuth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /* -------------------------------------------------------------- storage */

    public function storage(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->yields->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Yield record not found.'], 404);
        }
        return Response::json(['data' => $this->storage->forHarvest($ctx->farmId(), $id)]);
    }

    public function storeStorage(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('yields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->yields->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Yield record not found.'], 404);
        }

        $v = Validator::make($request->all(), [
            'storage_location' => ['max:160'],
            'drying_method' => ['max:120'],
            'drying_date' => ['date'],
            'quality_grade' => ['in:' . implode(',', self::GRADES)],
            'expected_loss_qty' => ['numeric', 'min:0', 'max:100000000'],
            'loss_reason' => ['max:255'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $this->storage->upsert($ctx->farmId(), $id, [
            'storage_location' => isset($d['storage_location']) && $d['storage_location'] !== '' ? trim((string) $d['storage_location']) : null,
            'drying_method' => isset($d['drying_method']) && $d['drying_method'] !== '' ? trim((string) $d['drying_method']) : null,
            'drying_date' => isset($d['drying_date']) && $d['drying_date'] !== '' ? date('Y-m-d', (int) strtotime((string) $d['drying_date'])) : null,
            'quality_grade' => isset($d['quality_grade']) && $d['quality_grade'] !== '' ? (string) $d['quality_grade'] : null,
            'expected_loss_qty' => isset($d['expected_loss_qty']) && $d['expected_loss_qty'] !== '' ? (float) $d['expected_loss_qty'] : null,
            'loss_reason' => isset($d['loss_reason']) && $d['loss_reason'] !== '' ? trim((string) $d['loss_reason']) : null,
            'notes' => isset($d['notes']) && $d['notes'] !== '' ? trim((string) $d['notes']) : null,
        ]);

        AuditLog::user('api.yield.storage_saved', (string) ApiAuth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->storage->forHarvest($ctx->farmId(), $id)]);
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request, bool $needCropField): array|Response
    {
        $rules = [
            'harvest_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'unit' => ['required', 'in:' . implode(',', self::UNITS)],
            'unit_weight' => ['numeric', 'min:0', 'max:100000'],
            'notes' => ['max:500'],
        ];
        if ($needCropField) {
            $rules['crop_field_id'] = ['required'];
        }

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $out = [
            'harvest_date' => date('Y-m-d', (int) strtotime((string) $d['harvest_date'])),
            'quantity' => (float) $d['quantity'],
            'unit' => (string) $d['unit'],
            'unit_weight' => isset($d['unit_weight']) && $d['unit_weight'] !== '' ? (float) $d['unit_weight'] : null,
            'notes' => isset($d['notes']) && $d['notes'] !== '' ? trim((string) $d['notes']) : null,
        ];

        if ($needCropField) {
            $ctx = FarmContext::current();
            if ($this->crops->find($ctx->farmId(), (string) $d['crop_field_id']) === null) {
                return Response::json(['errors' => ['crop_field_id' => ['Choose a valid crop planting.']]], 422);
            }
            $out['crop_field_id'] = (string) $d['crop_field_id'];
        }

        return $out;
    }
}
