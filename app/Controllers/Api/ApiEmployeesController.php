<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\EmployeeRepository;

final class ApiEmployeesController
{
    public function __construct(private readonly EmployeeRepository $employees = new EmployeeRepository())
    {
    }

    public function index(Request $request): Response
    {
        $activeOnly = $request->boolean('active_only');
        return Response::json(['data' => $this->employees->forFarm(FarmContext::current()->farmId(), $activeOnly)]);
    }

    public function show(Request $request): Response
    {
        $employee = $this->employees->find(FarmContext::current()->farmId(), (string) $request->route('id'));
        if ($employee === null) {
            return Response::json(['error' => 'Employee not found.'], 404);
        }
        return Response::json(['data' => $employee]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('employees.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $id = $this->employees->create($ctx->farmId(), $data);
        AuditLog::user('api.employee.created', (string) ApiAuth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->employees->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('employees.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->employees->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Employee not found.'], 404);
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $this->employees->update($ctx->farmId(), $id, $data);
        AuditLog::user('api.employee.updated', (string) ApiAuth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->employees->find($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('employees.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->employees->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Employee not found.'], 404);
        }

        if ($this->employees->hasLabourRecords($ctx->farmId(), $id)) {
            $this->employees->update($ctx->farmId(), $id, ['is_active' => 0]);
            AuditLog::user('api.employee.deactivated', (string) ApiAuth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
            return Response::json(['data' => $this->employees->find($ctx->farmId(), $id), 'note' => 'Has labour records — deactivated instead of deleted.']);
        }

        $this->employees->delete($ctx->farmId(), $id);
        AuditLog::user('api.employee.deleted', (string) ApiAuth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:120'],
            'role' => ['required', 'max:80'],
            'pay_rate' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'pay_rate_unit' => ['required', 'in:hour,day,month,task'],
            'phone' => ['max:40'],
            'is_active' => ['boolean'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        return [
            'name' => trim((string) $d['name']),
            'role' => trim((string) $d['role']),
            'pay_rate' => (float) $d['pay_rate'],
            'pay_rate_unit' => (string) $d['pay_rate_unit'],
            'phone' => isset($d['phone']) && $d['phone'] !== '' ? trim((string) $d['phone']) : null,
            'is_active' => $request->boolean('is_active') ? 1 : 0,
        ];
    }
}
