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
use App\Repositories\CropFieldRepository;
use App\Repositories\FieldRepository;

/**
 * Mobile activity capture: core fields only (type, date, field, notes). The
 * web app is where labour/input/other-cost breakdowns get added — this
 * mirrors the real product's mobile-vs-web split (offline-first quick
 * capture on mobile, full costing on web).
 */
final class ApiActivitiesController
{
    private const TYPES = [
        'Land Preparation', 'Planting', 'Fertiliser Application', 'Weeding',
        'Pest & Disease Control', 'Irrigation', 'Crop Management', 'Nursery',
        'Monitoring', 'Harvesting', 'Post-Harvest Handling', 'Storage',
        'Transport', 'Other',
    ];

    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $filters = array_filter([
            'field_id' => (string) $request->query('field_id', ''),
            'crop_field_id' => (string) $request->query('crop_field_id', ''),
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ]);
        return Response::json(['data' => $this->activities->forFarm($ctx->farmId(), $filters)]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('activities.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $core = $this->prepare($request, $ctx->farmId());
        if ($core instanceof Response) {
            return $core;
        }

        $id = $this->activities->create($ctx->farmId(), (string) ApiAuth::id(), $core, [], [], []);

        AuditLog::user('api.activity.created', (string) ApiAuth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->activities->findFull($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('activities.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        $existing = $this->activities->findFull($ctx->farmId(), $id);
        if ($existing === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $core = $this->prepare($request, $ctx->farmId());
        if ($core instanceof Response) {
            return $core;
        }

        $this->activities->update($ctx->farmId(), $id, $core, $existing['labour'], $existing['inputs'], $existing['other']);

        AuditLog::user('api.activity.updated', (string) ApiAuth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->activities->findFull($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('activities.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->activities->findFull($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $this->activities->delete($ctx->farmId(), $id);
        AuditLog::user('api.activity.deleted', (string) ApiAuth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /** @return array<string,mixed>|Response */
    private function prepare(Request $request, string $farmId): array|Response
    {
        $v = Validator::make($request->all(), [
            'field_id' => ['required'],
            'activity_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'date' => ['required', 'date'],
            'notes' => ['max:2000'],
            'responsible' => ['max:120'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        if ($this->fields->find($farmId, (string) $d['field_id']) === null) {
            return Response::json(['errors' => ['field_id' => ['Unknown field.']]], 422);
        }

        $cropFieldId = (string) $request->input('crop_field_id', '');
        if ($cropFieldId !== '' && $this->crops->find($farmId, $cropFieldId) === null) {
            return Response::json(['errors' => ['crop_field_id' => ['Unknown crop planting.']]], 422);
        }

        return [
            'field_id' => (string) $d['field_id'],
            'crop_field_id' => $cropFieldId !== '' ? $cropFieldId : null,
            'activity_type' => (string) $d['activity_type'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'notes' => trim((string) ($d['notes'] ?? '')) ?: null,
            'responsible_person_name' => trim((string) ($d['responsible'] ?? '')) ?: null,
            'responsible_employee_id' => null,
        ];
    }
}
