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
use App\Repositories\EquipmentRepository;

final class EquipmentController extends Controller
{
    private const CATEGORIES = ['tractor', 'irrigation', 'tool', 'vehicle', 'other'];
    private const STATUSES = ['active', 'under_repair', 'retired'];

    public function __construct(private readonly EquipmentRepository $equipment = new EquipmentRepository())
    {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('equipment/index', [
            'title'      => 'Equipment',
            'active'     => 'equipment',
            'rows'       => $this->equipment->forFarm($ctx->farmId()),
            'categories' => self::CATEGORIES,
            'statuses'   => self::STATUSES,
            'canManage'  => $ctx->can('equipment.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->equipment->create($ctx->farmId(), $data);
        AuditLog::user('equipment.created', (string) Auth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Equipment added.');
        return $this->redirect(url('equipment/' . $id));
    }

    public function show(Request $request): Response
    {
        $ctx = FarmContext::current();
        $item = $this->equipment->find($ctx->farmId(), (string) $request->route('id'));
        if ($item === null) {
            Flash::error('Equipment not found.');
            return $this->redirect(url('equipment'));
        }
        return $this->view('equipment/show', [
            'title'     => (string) $item['name'],
            'active'    => 'equipment',
            'e'         => $item,
            'logs'      => $this->equipment->logsFor($ctx->farmId(), (string) $item['id']),
            'canManage' => $ctx->can('equipment.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $item = $this->equipment->find($ctx->farmId(), (string) $request->route('id'));
        if ($item === null) {
            Flash::error('Equipment not found.');
            return $this->redirect(url('equipment'));
        }
        return $this->form($item);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            Flash::error('Equipment not found.');
            return $this->redirect(url('equipment'));
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->equipment->update($ctx->farmId(), $id, $data);
        AuditLog::user('equipment.updated', (string) Auth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Equipment updated.');
        return $this->redirect(url('equipment/' . $id));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            Flash::error('Equipment not found.');
            return $this->redirect(url('equipment'));
        }
        $this->equipment->delete($ctx->farmId(), $id);
        AuditLog::user('equipment.deleted', (string) Auth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Equipment deleted.');
        return $this->redirect(url('equipment'));
    }

    /* ------------------------------------------------------------ maintenance */

    public function addLog(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->equipment->find($ctx->farmId(), $id) === null) {
            Flash::error('Equipment not found.');
            return $this->redirect(url('equipment'));
        }

        $data = $this->validate($request, [
            'date'        => ['required', 'date'],
            'description' => ['required', 'max:255'],
            'cost'        => ['numeric', 'min:0', 'max:100000000'],
            'hours_used'  => ['numeric', 'min:0', 'max:1000000'],
            'notes'       => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $this->equipment->addLog($ctx->farmId(), $id, [
            'date'        => date('Y-m-d', (int) strtotime((string) $data['date'])),
            'description' => trim((string) $data['description']),
            'cost'        => isset($data['cost']) && $data['cost'] !== '' ? round((float) $data['cost'], 2) : 0,
            'hours_used'  => isset($data['hours_used']) && $data['hours_used'] !== '' ? (float) $data['hours_used'] : null,
            'notes'       => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ]);

        AuditLog::user('equipment.log_added', (string) Auth::id(), ['equipment_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Maintenance record added.');
        return $this->redirect(url('equipment/' . $id));
    }

    public function deleteLog(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        $logId = (string) $request->route('logId');
        $this->equipment->deleteLog($ctx->farmId(), $logId);
        Flash::success('Maintenance record removed.');
        return $this->redirect(url('equipment/' . $id));
    }

    /* ---------------------------------------------------------------- helpers */

    private function form(?array $item): Response
    {
        return $this->view('equipment/form', [
            'title'      => $item === null ? 'Add equipment' : 'Edit equipment',
            'active'     => 'equipment',
            'e'          => $item,
            'categories' => self::CATEGORIES,
            'statuses'   => self::STATUSES,
        ]);
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'name'             => ['required', 'max:160'],
            'category'         => ['required', 'in:' . implode(',', self::CATEGORIES)],
            'status'           => ['required', 'in:' . implode(',', self::STATUSES)],
            'acquisition_date' => ['date'],
            'acquisition_cost' => ['numeric', 'min:0', 'max:100000000'],
            'notes'            => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        return [
            'name'             => trim((string) $data['name']),
            'category'         => (string) $data['category'],
            'status'           => (string) $data['status'],
            'acquisition_date' => isset($data['acquisition_date']) && $data['acquisition_date'] !== '' ? date('Y-m-d', (int) strtotime((string) $data['acquisition_date'])) : null,
            'acquisition_cost' => isset($data['acquisition_cost']) && $data['acquisition_cost'] !== '' ? (float) $data['acquisition_cost'] : null,
            'notes'            => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ];
    }
}
