<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\EmployeeRepository;

final class EmployeesController extends Controller
{
    public function __construct(private readonly EmployeeRepository $employees = new EmployeeRepository())
    {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('employees/index', [
            'title'     => 'Employees',
            'active'    => 'employees',
            'employees' => $this->employees->forFarm($ctx->farmId()),
            'canManage' => $ctx->can('employees.manage') && !$ctx->isReadOnly(),
            'payroll'   => $ctx->feature('payroll_tracking'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('employees/form', ['title' => 'Add employee', 'active' => 'employees', 'employee' => null]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->employees->create($ctx->farmId(), $data);
        AuditLog::user('employee.created', (string) Auth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Employee added.');
        return $this->redirect(url('employees'));
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $employee = $this->employees->find($ctx->farmId(), (string) $request->route('id'));
        if ($employee === null) {
            Flash::error('Employee not found.');
            return $this->redirect(url('employees'));
        }
        return $this->view('employees/form', ['title' => 'Edit employee', 'active' => 'employees', 'employee' => $employee]);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->employees->find($ctx->farmId(), $id) === null) {
            Flash::error('Employee not found.');
            return $this->redirect(url('employees'));
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->employees->update($ctx->farmId(), $id, $data);
        AuditLog::user('employee.updated', (string) Auth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Employee updated.');
        return $this->redirect(url('employees'));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->employees->find($ctx->farmId(), $id) === null) {
            Flash::error('Employee not found.');
            return $this->redirect(url('employees'));
        }
        if ($this->employees->hasLabourRecords($ctx->farmId(), $id)) {
            // Keep the audit trail intact — deactivate instead of delete.
            $this->employees->update($ctx->farmId(), $id, ['is_active' => 0]);
            Flash::info('This employee has labour records, so they were deactivated rather than deleted.');
            return $this->redirect(url('employees'));
        }
        $this->employees->delete($ctx->farmId(), $id);
        AuditLog::user('employee.deleted', (string) Auth::id(), ['employee_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Employee removed.');
        return $this->redirect(url('employees'));
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'name'          => ['required', 'max:120'],
            'role'          => ['required', 'max:80'],
            'pay_rate'      => ['required', 'numeric', 'min:0', 'max:100000000'],
            'pay_rate_unit' => ['required', 'in:hour,day,month,task'],
            'phone'         => ['max:40'],
            'is_active'     => ['boolean'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        return [
            'name'          => trim((string) $data['name']),
            'role'          => trim((string) $data['role']),
            'pay_rate'      => (float) $data['pay_rate'],
            'pay_rate_unit' => (string) $data['pay_rate_unit'],
            'phone'         => isset($data['phone']) && $data['phone'] !== '' ? trim((string) $data['phone']) : null,
            'is_active'     => $request->boolean('is_active') ? 1 : 0,
        ];
    }
}
