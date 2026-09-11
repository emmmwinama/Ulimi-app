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
use App\Repositories\ActivityRepository;
use App\Repositories\CropFieldRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\FieldRepository;
use App\Services\CropTimeline;

final class ActivitiesController extends Controller
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
        private readonly EmployeeRepository $employees = new EmployeeRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $filters = [
            'field_id' => (string) $request->query('field_id', ''),
            'type'     => (string) $request->query('type', ''),
            'from'     => $this->dateOrEmpty((string) $request->query('from', '')),
            'to'       => $this->dateOrEmpty((string) $request->query('to', '')),
        ];

        $rows = $this->activities->forFarm($ctx->farmId(), $filters);

        $byType = [];
        $byField = [];
        $bySeason = [];
        $activeCount = 0;
        foreach ($rows as $r) {
            $type = (string) $r['activity_type'];
            $byType[$type] ??= ['type' => $type, 'count' => 0, 'total_cost' => 0.0];
            $byType[$type]['count']++;
            $byType[$type]['total_cost'] += (float) $r['total_cost'];

            $fieldName = (string) $r['field_name'];
            $byField[$fieldName] ??= ['name' => $fieldName, 'count' => 0, 'total_cost' => 0.0];
            $byField[$fieldName]['count']++;
            $byField[$fieldName]['total_cost'] += (float) $r['total_cost'];

            $season = (string) ($r['season'] ?? '');
            if ($season !== '') {
                $bySeason[$season] ??= ['season' => $season, 'count' => 0, 'total_cost' => 0.0];
                $bySeason[$season]['count']++;
                $bySeason[$season]['total_cost'] += (float) $r['total_cost'];
            }

            $historical = (int) ($r['crop_archived'] ?? 0) === 1 || (string) ($r['crop_status'] ?? '') === 'Harvested';
            if (!$historical) {
                $activeCount++;
            }
        }
        usort($byType, static fn ($a, $b) => $b['count'] <=> $a['count']);
        usort($byField, static fn ($a, $b) => $b['count'] <=> $a['count']);
        usort($bySeason, static fn ($a, $b) => $b['count'] <=> $a['count']);

        return $this->view('activities/index', [
            'title'       => 'Activities',
            'active'      => 'activities',
            'rows'        => $rows,
            'fields'      => $this->fields->forFarm($ctx->farmId()),
            'types'       => self::TYPES,
            'filters'     => $filters,
            'canManage'   => $ctx->can('activities.manage') && !$ctx->isReadOnly(),
            'byType'      => array_values($byType),
            'byField'     => array_values($byField),
            'bySeason'    => array_values($bySeason),
            'activeCount' => $activeCount,
        ]);
    }

    public function create(Request $request): Response
    {
        $ctx = FarmContext::current();
        $fields = $this->fields->forFarm($ctx->farmId());
        if ($fields === []) {
            Flash::info('Add a field first.');
            return $this->redirect(url('fields/create'));
        }
        return $this->form($ctx, null);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $prepared = $this->prepare($request);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $id = $this->activities->create(
            $ctx->farmId(),
            (string) Auth::id(),
            $prepared['core'],
            $prepared['labour'],
            $prepared['inputs'],
            $prepared['other'],
        );
        AuditLog::user('activity.created', (string) Auth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Activity logged.');
        return $this->redirect(url('activities/' . $id));
    }

    public function show(Request $request): Response
    {
        $ctx = FarmContext::current();
        $activity = $this->activities->findFull($ctx->farmId(), (string) $request->route('id'));
        if ($activity === null) {
            Flash::error('Activity not found.');
            return $this->redirect(url('activities'));
        }

        $labourSum = array_sum(array_map(static fn ($r) => (float) $r['total_cost'], $activity['labour']));
        $inputSum  = array_sum(array_map(static fn ($r) => (float) $r['total_cost'], $activity['inputs']));
        $otherSum  = array_sum(array_map(static fn ($r) => (float) $r['amount'], $activity['other']));

        return $this->view('activities/show', [
            'title'     => (string) $activity['activity_type'] . ' — ' . (string) $activity['field_name'],
            'active'    => 'activities',
            'a'         => $activity,
            'labourSum' => $labourSum,
            'inputSum'  => $inputSum,
            'otherSum'  => $otherSum,
            'grandTotal'=> $labourSum + $inputSum + $otherSum,
            'canManage' => $ctx->can('activities.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $activity = $this->activities->findFull($ctx->farmId(), (string) $request->route('id'));
        if ($activity === null) {
            Flash::error('Activity not found.');
            return $this->redirect(url('activities'));
        }
        return $this->form($ctx, $activity);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->activities->findFull($ctx->farmId(), $id) === null) {
            Flash::error('Activity not found.');
            return $this->redirect(url('activities'));
        }

        $prepared = $this->prepare($request);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $this->activities->update(
            $ctx->farmId(),
            $id,
            $prepared['core'],
            $prepared['labour'],
            $prepared['inputs'],
            $prepared['other'],
        );
        AuditLog::user('activity.updated', (string) Auth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Activity updated.');
        return $this->redirect(url('activities/' . $id));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->activities->findFull($ctx->farmId(), $id) === null) {
            Flash::error('Activity not found.');
            return $this->redirect(url('activities'));
        }
        $this->activities->delete($ctx->farmId(), $id);
        AuditLog::user('activity.deleted', (string) Auth::id(), ['activity_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Activity deleted.');
        return $this->redirect(url('activities'));
    }

    /* ---------------------------------------------------------------- helpers */

    private function form(FarmContext $ctx, ?array $activity): Response
    {
        return $this->view('activities/form', [
            'title'     => $activity === null ? 'Log activity' : 'Edit activity',
            'active'    => 'activities',
            'a'         => $activity,
            'fields'    => $this->fields->forFarm($ctx->farmId()),
            'crops'     => $this->crops->forFarm($ctx->farmId()),
            'employees' => $this->employees->forFarm($ctx->farmId(), true),
            'types'     => self::TYPES,
            'payroll'   => $ctx->feature('payroll_tracking'),
        ]);
    }

    /**
     * @return array{core:array<string,mixed>,labour:list<array<string,mixed>>,inputs:list<array<string,mixed>>,other:list<array<string,mixed>>}|Response
     */
    private function prepare(Request $request): array|Response
    {
        $ctx = FarmContext::current();

        $data = $this->validate($request, [
            'field_id'      => ['required'],
            'activity_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'date'          => ['required', 'date'],
            'notes'         => ['max:2000'],
            'responsible'   => ['max:120'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        if ($this->fields->find($ctx->farmId(), (string) $data['field_id']) === null) {
            return $this->fieldError($request, 'field_id', 'Choose one of your fields.');
        }

        $cropFieldId = (string) $request->input('crop_field_id', '');
        if ($cropFieldId !== '' && $this->crops->find($ctx->farmId(), $cropFieldId) === null) {
            return $this->fieldError($request, 'crop_field_id', 'Choose a valid crop planting.');
        }

        // Responsible person: either a roster employee or a free-text name.
        $employeeId = (string) $request->input('responsible_employee_id', '');
        if ($employeeId !== '' && $this->employees->find($ctx->farmId(), $employeeId) === null) {
            $employeeId = '';
        }

        $core = [
            'field_id'                => (string) $data['field_id'],
            'crop_field_id'           => $cropFieldId !== '' ? $cropFieldId : null,
            'activity_type'           => (string) $data['activity_type'],
            'date'                    => date('Y-m-d', (int) strtotime((string) $data['date'])),
            'notes'                   => trim((string) ($data['notes'] ?? '')) ?: null,
            'responsible_person_name' => $employeeId === '' ? (trim((string) ($data['responsible'] ?? '')) ?: null) : null,
            'responsible_employee_id' => $employeeId ?: null,
        ];

        return [
            'core'   => $core,
            'labour' => $this->parseLabour($request, $ctx->farmId()),
            'inputs' => $this->parseInputs($request),
            'other'  => $this->parseOther($request),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function parseLabour(Request $request, string $farmId): array
    {
        $emp   = (array) $request->input('labour_employee', []);
        $name  = (array) $request->input('labour_worker', []);
        $hours = (array) $request->input('labour_hours', []);
        $days  = (array) $request->input('labour_days', []);
        $cost  = (array) $request->input('labour_cost', []);

        $out = [];
        $count = max(count($emp), count($name), count($hours), count($days), count($cost));
        for ($i = 0; $i < $count; $i++) {
            $employeeId = trim((string) ($emp[$i] ?? ''));
            $workerName = trim((string) ($name[$i] ?? ''));
            $h = (float) ($hours[$i] ?? 0);
            $d = (float) ($days[$i] ?? 0);
            $c = (float) ($cost[$i] ?? 0);

            if ($employeeId === '' && $workerName === '' && $h <= 0 && $d <= 0 && $c <= 0) {
                continue;
            }
            if ($employeeId !== '' && $this->employees->find($farmId, $employeeId) === null) {
                $employeeId = '';
            }
            // Fall back to pay-rate * quantity if no explicit cost given.
            if ($c <= 0 && $employeeId !== '') {
                $e = $this->employees->find($farmId, $employeeId);
                if ($e !== null) {
                    $rate = (float) $e['pay_rate'];
                    $c = match ((string) $e['pay_rate_unit']) {
                        'hour'  => $rate * $h,
                        'day'   => $rate * $d,
                        default => $rate,
                    };
                }
            }
            $out[] = [
                'employee_id'  => $employeeId,
                'worker_name'  => $workerName,
                'hours_worked' => max(0, $h),
                'days_worked'  => max(0, $d),
                'total_cost'   => round(max(0, $c), 2),
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function parseInputs(Request $request): array
    {
        $names = (array) $request->input('input_name', []);
        $cats  = (array) $request->input('input_category', []);
        $qtys  = (array) $request->input('input_qty', []);
        $units = (array) $request->input('input_unit', []);
        $ucost = (array) $request->input('input_unitcost', []);

        $out = [];
        foreach ($names as $i => $rawName) {
            $name = trim((string) $rawName);
            if ($name === '') {
                continue;
            }
            $q = (float) ($qtys[$i] ?? 0);
            $u = (float) ($ucost[$i] ?? 0);
            $out[] = [
                'input_name' => mb_substr($name, 0, 160),
                'category'   => mb_substr(trim((string) ($cats[$i] ?? 'Other')) ?: 'Other', 0, 60),
                'quantity'   => max(0, $q),
                'unit'       => mb_substr(trim((string) ($units[$i] ?? '')), 0, 20),
                'unit_cost'  => round(max(0, $u), 2),
                'total_cost' => round(max(0, $q * $u), 2),
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function parseOther(Request $request): array
    {
        $descs = (array) $request->input('other_desc', []);
        $amts  = (array) $request->input('other_amount', []);

        $out = [];
        foreach ($descs as $i => $rawDesc) {
            $desc = trim((string) $rawDesc);
            if ($desc === '') {
                continue;
            }
            $out[] = [
                'description' => mb_substr($desc, 0, 200),
                'amount'      => round(max(0, (float) ($amts[$i] ?? 0)), 2),
            ];
        }
        return $out;
    }

    private function dateOrEmpty(string $v): string
    {
        $ts = $v !== '' ? strtotime($v) : false;
        return $ts === false ? '' : date('Y-m-d', $ts);
    }
}
