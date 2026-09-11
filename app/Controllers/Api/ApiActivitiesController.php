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

/**
 * Mobile activity capture: core fields only (type, date, field, notes). The
 * web app is where labour/input/other-cost breakdowns get added — this
 * mirrors the real product's mobile-vs-web split (offline-first quick
 * capture on mobile, full costing on web).
 */
final class ApiActivitiesController
{
    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return Response::json(['data' => $this->activities->forFarm($ctx->farmId())]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('activities.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $v = Validator::make($request->all(), [
            'field_id' => ['required'],
            'activity_type' => ['required', 'max:80'],
            'date' => ['required', 'date'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        if ($this->fields->find($ctx->farmId(), (string) $d['field_id']) === null) {
            return Response::json(['errors' => ['field_id' => ['Unknown field.']]], 422);
        }

        $cropFieldId = (string) $request->input('crop_field_id', '');

        $id = $this->activities->create(
            $ctx->farmId(),
            (string) ApiAuth::id(),
            [
                'field_id' => (string) $d['field_id'],
                'crop_field_id' => $cropFieldId !== '' ? $cropFieldId : null,
                'activity_type' => (string) $d['activity_type'],
                'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
                'notes' => $request->input('notes'),
                'responsible_person_name' => $request->input('responsible'),
                'responsible_employee_id' => null,
            ],
            [], [], [],
        );

        AuditLog::user('api.activity.created', (string) ApiAuth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->activities->findFull($ctx->farmId(), $id)], 201);
    }
}
