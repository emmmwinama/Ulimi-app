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
use App\Repositories\CropTypeRepository;
use App\Repositories\FieldRepository;

final class ApiCropsController
{
    public function __construct(
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly CropTypeRepository $cropTypes = new CropTypeRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $farmId = FarmContext::current()->farmId();
        $filters = [
            'season'   => (string) $request->query('season', ''),
            'field_id' => (string) $request->query('field_id', ''),
            'archived' => $request->query('archived') === '1',
        ];
        return Response::json(['data' => $this->crops->forFarm($farmId, $filters)]);
    }

    public function show(Request $request): Response
    {
        $farmId = FarmContext::current()->farmId();
        $crop = $this->crops->find($farmId, (string) $request->route('id'));
        if ($crop === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }
        return Response::json(['data' => $crop]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $prepared = $this->prepare($request, null);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $id = $this->crops->create($ctx->farmId(), $prepared);
        AuditLog::user('api.crop.created', (string) ApiAuth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->crops->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->crops->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $prepared = $this->prepare($request, $id);
        if ($prepared instanceof Response) {
            return $prepared;
        }
        unset($prepared['field_id']); // field is fixed after creation, matches web behaviour

        $this->crops->update($ctx->farmId(), $id, $prepared);
        AuditLog::user('api.crop.updated', (string) ApiAuth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->crops->find($ctx->farmId(), $id)]);
    }

    public function archive(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->crops->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $reason = mb_substr(trim((string) $request->input('reason', '')), 0, 255);
        $this->crops->archive($ctx->farmId(), $id, $reason);
        AuditLog::user('api.crop.archived', (string) ApiAuth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->crops->find($ctx->farmId(), $id)]);
    }

    public function restore(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->crops->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $this->crops->restore($ctx->farmId(), $id);
        AuditLog::user('api.crop.restored', (string) ApiAuth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->crops->find($ctx->farmId(), $id)]);
    }

    /**
     * @param string|null $excludeId the planting's own id when editing it, so its
     *   current area doesn't count against itself in the remaining-acreage check
     * @return array<string,mixed>|Response
     */
    private function prepare(Request $request, ?string $excludeId): array|Response
    {
        $ctx = FarmContext::current();

        $v = Validator::make($request->all(), [
            'field_id'              => ['required'],
            'crop_type'             => ['required', 'max:120'],
            'variety'               => ['max:120'],
            'area_planted'          => ['required', 'numeric', 'min:0', 'max:100000'],
            'season'                => ['required', 'max:60'],
            'planting_date'         => ['required', 'date'],
            'expected_harvest_date' => ['required', 'date'],
            'status'                => ['required', 'in:Active,Harvested,Failed,Terminated'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $field = $this->fields->find($ctx->farmId(), (string) $d['field_id']);
        if ($field === null) {
            return Response::json(['errors' => ['field_id' => ['Choose one of your fields.']]], 422);
        }

        $plant = date('Y-m-d', (int) strtotime((string) $d['planting_date']));
        $harvest = date('Y-m-d', (int) strtotime((string) $d['expected_harvest_date']));
        if ($harvest < $plant) {
            return Response::json(['errors' => ['expected_harvest_date' => ['Expected harvest cannot be before planting.']]], 422);
        }

        // A planting only claims land while it's Active — matches web parity
        // (App\Controllers\Farm\CropsController::prepare).
        $areaPlanted = (float) $d['area_planted'];
        if ((string) $d['status'] === 'Active') {
            $allocated = $this->crops->activeAllocatedArea($ctx->farmId(), (string) $field['id'], $excludeId);
            $remaining = (float) $field['cultivatable_area'] - $allocated;
            if ($areaPlanted > $remaining + 1e-9) {
                return Response::json(['errors' => ['area_planted' => [sprintf(
                    'Only %.2f ha remaining on %s (%.2f of %.2f ha cultivatable already planted).',
                    max(0.0, $remaining), (string) $field['name'], $allocated, (float) $field['cultivatable_area'],
                )]]], 422);
            }
        }

        return [
            'field_id'              => (string) $d['field_id'],
            'crop_type_id'          => $this->cropTypes->resolveOrCreate($ctx->farmId(), (string) $d['crop_type']),
            'variety'               => isset($d['variety']) ? trim((string) $d['variety']) : '',
            'area_planted'          => (float) $d['area_planted'],
            'season'                => trim((string) $d['season']),
            'planting_date'         => $plant,
            'expected_harvest_date' => $harvest,
            'status'                => (string) $d['status'],
        ];
    }
}
