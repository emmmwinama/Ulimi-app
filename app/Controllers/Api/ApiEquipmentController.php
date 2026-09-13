<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\EquipmentRepository;

final class ApiEquipmentController
{
    private const CATEGORIES = ['tractor', 'irrigation', 'tool', 'vehicle', 'other'];
    private const STATUSES = ['active', 'under_repair', 'retired'];

    public function __construct(private readonly EquipmentRepository $equipment = new EquipmentRepository())
    {
    }

    public function index(Request $request): Response
    {
        return Response::json(['data' => $this->equipment->forFarm(FarmContext::current()->farmId())]);
    }

    public function show(Request $request): Response
    {
        $item = $this->equipment->find(FarmContext::current()->farmId(), (string) $request->route('id'));
        if ($item === null) {
            return Response::json(['error' => 'Equipment not found.'], 404);
        }
        return Response::json(['data' => $item]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('equipment.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $id = $this->equipment->create($ctx->farmId(), $data);
        AuditLog::user('api.equipment.created', (string) ApiAuth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->equipment->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('equipment.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Equipment not found.'], 404);
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $this->equipment->update($ctx->farmId(), $id, $data);
        AuditLog::user('api.equipment.updated', (string) ApiAuth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->equipment->find($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('equipment.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Equipment not found.'], 404);
        }

        $this->equipment->delete($ctx->farmId(), $id);
        AuditLog::user('api.equipment.deleted', (string) ApiAuth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /* ------------------------------------------------------------ maintenance */

    public function logs(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Equipment not found.'], 404);
        }
        return Response::json(['data' => $this->equipment->logsFor($ctx->farmId(), $id)]);
    }

    public function addLog(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('equipment.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Equipment not found.'], 404);
        }

        $v = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'description' => ['required', 'max:255'],
            'cost' => ['numeric', 'min:0', 'max:100000000'],
            'hours_used' => ['numeric', 'min:0', 'max:1000000'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $logId = $this->equipment->addLog($ctx->farmId(), $id, [
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'description' => trim((string) $d['description']),
            'cost' => isset($d['cost']) && $d['cost'] !== '' ? round((float) $d['cost'], 2) : 0,
            'hours_used' => isset($d['hours_used']) && $d['hours_used'] !== '' ? (float) $d['hours_used'] : null,
            'notes' => isset($d['notes']) && $d['notes'] !== '' ? trim((string) $d['notes']) : null,
        ]);

        AuditLog::user('api.equipment.log_added', (string) ApiAuth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => ['id' => $logId]], 201);
    }

    public function deleteLog(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('equipment.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        $logId = (string) $request->route('logId');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Equipment not found.'], 404);
        }

        $this->equipment->deleteLog($ctx->farmId(), $logId);
        AuditLog::user('api.equipment.log_deleted', (string) ApiAuth::id(), ['equipment_id' => $id, 'log_id' => $logId], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:160'],
            'category' => ['required', 'in:' . implode(',', self::CATEGORIES)],
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'acquisition_date' => ['date'],
            'acquisition_cost' => ['numeric', 'min:0', 'max:100000000'],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        return [
            'name' => trim((string) $d['name']),
            'category' => (string) $d['category'],
            'status' => (string) $d['status'],
            'acquisition_date' => isset($d['acquisition_date']) && $d['acquisition_date'] !== '' ? date('Y-m-d', (int) strtotime((string) $d['acquisition_date'])) : null,
            'acquisition_cost' => isset($d['acquisition_cost']) && $d['acquisition_cost'] !== '' ? (float) $d['acquisition_cost'] : null,
            'notes' => isset($d['notes']) && $d['notes'] !== '' ? trim((string) $d['notes']) : null,
        ];
    }
}
