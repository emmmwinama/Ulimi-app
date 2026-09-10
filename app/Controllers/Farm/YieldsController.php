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
use App\Repositories\CropFieldRepository;
use App\Repositories\HarvestYieldRepository;

final class YieldsController extends Controller
{
    private const UNITS = ['kg', 'bags', 'tonnes', 'crates'];

    public function __construct(
        private readonly HarvestYieldRepository $yields = new HarvestYieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $rows = $this->yields->forFarm($ctx->farmId());
        $totalKg = array_sum(array_map(static fn ($r) => (float) $r['quantity_kg'], $rows));

        return $this->view('yields/index', [
            'title'     => 'Yields',
            'active'    => 'yields',
            'rows'      => $rows,
            'totalKg'   => $totalKg,
            'canManage' => $ctx->can('yields.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function create(Request $request): Response
    {
        $ctx = FarmContext::current();
        $crops = $this->crops->forFarm($ctx->farmId());
        if ($crops === []) {
            Flash::info('Record a crop planting first — yields are logged against a planting.');
            return $this->redirect(url('crops/create'));
        }
        return $this->view('yields/form', [
            'title'  => 'Record yield',
            'active' => 'yields',
            'row'    => null,
            'crops'  => $crops,
            'units'  => self::UNITS,
            'preselect' => (string) $request->query('crop_field_id', ''),
        ]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validated($request, true);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->yields->create($ctx->farmId(), $data);
        AuditLog::user('yield.created', (string) Auth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Yield recorded.');
        return $this->redirect(url('yields'));
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $row = $this->yields->find($ctx->farmId(), (string) $request->route('id'));
        if ($row === null) {
            Flash::error('Yield record not found.');
            return $this->redirect(url('yields'));
        }
        return $this->view('yields/form', [
            'title'  => 'Edit yield',
            'active' => 'yields',
            'row'    => $row,
            'crops'  => $this->crops->forFarm($ctx->farmId()),
            'units'  => self::UNITS,
            'preselect' => (string) $row['crop_field_id'],
        ]);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->yields->find($ctx->farmId(), $id) === null) {
            Flash::error('Yield record not found.');
            return $this->redirect(url('yields'));
        }
        $data = $this->validated($request, false);
        if ($data instanceof Response) {
            return $data;
        }
        $this->yields->update($ctx->farmId(), $id, $data);
        AuditLog::user('yield.updated', (string) Auth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Yield updated.');
        return $this->redirect(url('yields'));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->yields->find($ctx->farmId(), $id) === null) {
            Flash::error('Yield record not found.');
            return $this->redirect(url('yields'));
        }
        $this->yields->delete($ctx->farmId(), $id);
        AuditLog::user('yield.deleted', (string) Auth::id(), ['yield_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Yield record deleted.');
        return $this->redirect(url('yields'));
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request, bool $needCropField): array|Response
    {
        $rules = [
            'harvest_date' => ['required', 'date'],
            'quantity'     => ['required', 'numeric', 'min:0', 'max:100000000'],
            'unit'         => ['required', 'in:' . implode(',', self::UNITS)],
            'unit_weight'  => ['numeric', 'min:0', 'max:100000'],
            'notes'        => ['max:500'],
        ];
        if ($needCropField) {
            $rules['crop_field_id'] = ['required'];
        }

        $data = $this->validate($request, $rules);
        if ($data instanceof Response) {
            return $data;
        }

        $out = [
            'harvest_date' => date('Y-m-d', (int) strtotime((string) $data['harvest_date'])),
            'quantity'     => (float) $data['quantity'],
            'unit'         => (string) $data['unit'],
            'unit_weight'  => isset($data['unit_weight']) && $data['unit_weight'] !== '' ? (float) $data['unit_weight'] : null,
            'notes'        => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ];

        if ($needCropField) {
            $ctx = FarmContext::current();
            if ($this->crops->find($ctx->farmId(), (string) $data['crop_field_id']) === null) {
                return $this->fieldError($request, 'crop_field_id', 'Choose a valid crop planting.');
            }
            $out['crop_field_id'] = (string) $data['crop_field_id'];
        }

        return $out;
    }
}
