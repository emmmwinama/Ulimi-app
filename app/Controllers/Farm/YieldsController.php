<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
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
        $farmId = $ctx->farmId();
        $rows = $this->yields->forFarm($farmId);
        $totalKg = array_sum(array_map(static fn ($r) => (float) $r['quantity_kg'], $rows));
        $season = (string) $request->query('season', '');
        $margin = max(0, min(200, (int) $request->query('margin', 30)));

        $db = Database::instance();

        // Activity cost per crop planting (labour + inputs + other costs).
        $costByCropField = [];
        foreach (['activity_labour' => 'total_cost', 'activity_inputs' => 'total_cost', 'activity_other_costs' => 'amount'] as $table => $col) {
            foreach ($db->select(
                "SELECT fa.crop_field_id, COALESCE(SUM(t.$col), 0) AS cost
                 FROM $table t JOIN farm_activities fa ON fa.id = t.activity_id
                 WHERE fa.farm_id = :fid AND fa.crop_field_id IS NOT NULL
                 GROUP BY fa.crop_field_id",
                ['fid' => $farmId],
            ) as $row) {
                $costByCropField[$row['crop_field_id']] = ($costByCropField[$row['crop_field_id']] ?? 0.0) + (float) $row['cost'];
            }
        }

        $groups = [];
        $seasons = [];
        foreach ($rows as $r) {
            $s = (string) ($r['season'] ?? '');
            if ($s !== '' && !in_array($s, $seasons, true)) {
                $seasons[] = $s;
            }
            if ($season !== '' && $s !== $season) {
                continue;
            }
            $cfId = (string) $r['crop_field_id'];
            if (!isset($groups[$cfId])) {
                $groups[$cfId] = [
                    'crop_field_id' => $cfId,
                    'crop_name'     => $r['crop_name'],
                    'variety'       => $r['variety'],
                    'field_name'    => $r['field_name'],
                    'season'        => $r['season'],
                    'status'        => $r['crop_status'],
                    'area_planted'  => (float) $r['area_planted'],
                    'total_yield_kg'=> 0.0,
                    'total_cost'    => $costByCropField[$cfId] ?? 0.0,
                    'harvests'      => [],
                ];
            }
            $groups[$cfId]['total_yield_kg'] += (float) $r['quantity_kg'];
            $groups[$cfId]['harvests'][] = $r;
        }
        foreach ($groups as $cfId => &$g) {
            $g['cost_per_ha'] = $g['area_planted'] > 0 ? $g['total_cost'] / $g['area_planted'] : null;
            $g['yield_per_ha'] = $g['area_planted'] > 0 ? $g['total_yield_kg'] / $g['area_planted'] : null;
            $costPerKg = $g['total_yield_kg'] > 0 ? $g['total_cost'] / $g['total_yield_kg'] : 0.0;
            $g['break_even_per_kg'] = $costPerKg;
            $g['break_even_per_bag50'] = $costPerKg * 50;
            $g['break_even_per_tonne'] = $costPerKg * 1000;
            $suggestedPerKg = $costPerKg * (1 + $margin / 100);
            $g['suggested_per_kg'] = $suggestedPerKg;
            $g['suggested_per_bag50'] = $suggestedPerKg * 50;
            $g['projected_profit'] = ($suggestedPerKg - $costPerKg) * $g['total_yield_kg'];
        }
        unset($g);

        // By crop type: aggregate area/yield/cost across all crop_field groups.
        $byType = [];
        foreach ($groups as $g) {
            $name = (string) $g['crop_name'];
            $byType[$name] ??= ['crop_name' => $name, 'total_yield_kg' => 0.0, 'total_area' => 0.0, 'total_cost' => 0.0];
            $byType[$name]['total_yield_kg'] += $g['total_yield_kg'];
            $byType[$name]['total_area'] += $g['area_planted'];
            $byType[$name]['total_cost'] += $g['total_cost'];
        }

        return $this->view('yields/index', [
            'title'     => 'Yields',
            'active'    => 'yields',
            'rows'      => $rows,
            'totalKg'   => $totalKg,
            'canManage' => $ctx->can('yields.manage') && !$ctx->isReadOnly(),
            'groups'    => array_values($groups),
            'byType'    => array_values($byType),
            'seasons'   => $seasons,
            'season'    => $season,
            'margin'    => $margin,
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
