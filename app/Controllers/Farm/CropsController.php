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
use App\Repositories\CropTypeRepository;
use App\Repositories\FieldRepository;
use App\Services\CropTimeline;
use App\Support\Seasons;

final class CropsController extends Controller
{
    private const STATUSES = ['Active', 'Harvested', 'Failed', 'Terminated'];

    public function __construct(
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly CropTypeRepository $cropTypes = new CropTypeRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $season = (string) $request->query('season', '');
        $fieldId = (string) $request->query('field_id', '');
        $status = (string) $request->query('status', '');
        $archived = $request->query('view') === 'archived';
        $canManage = $ctx->can('crops.manage') && !$ctx->isReadOnly();
        $allFields = $this->fields->forFarm($ctx->farmId());

        $crops = $this->crops->forFarm($ctx->farmId(), [
            'season'   => $season,
            'field_id' => $fieldId,
            'status'   => $status,
            'archived' => $archived,
        ]);

        $today = strtotime('today');
        $dueSoonOrOverdue = 0;
        foreach ($crops as $c) {
            if ((int) $c['is_archived'] === 1 || empty($c['expected_harvest_date'])) {
                continue;
            }
            $days = (int) floor((strtotime((string) $c['expected_harvest_date']) - $today) / 86400);
            if ($days < 14) {
                $dueSoonOrOverdue++;
            }
        }

        return $this->view('crops/index', [
            'title'         => 'Crops',
            'active'        => 'crops',
            'crops'         => $crops,
            'stats'         => [
                'total_area' => array_sum(array_map(static fn ($c) => (float) $c['area_planted'], $crops)),
                'active'     => count(array_filter($crops, static fn ($c) => $c['status'] === 'Active')),
                'due_soon'   => $dueSoonOrOverdue,
            ],
            'seasons'       => $this->crops->seasons($ctx->farmId()),
            'allFields'     => $allFields,
            'statuses'      => self::STATUSES,
            'filters'       => ['season' => $season, 'field_id' => $fieldId, 'status' => $status],
            'season'        => $season,
            'archived'      => $archived,
            'canManage'     => $canManage,
            'fields'        => $canManage && !$archived ? $allFields : [],
            'cropTypes'     => $canManage && !$archived ? $this->cropTypes->available($ctx->farmId()) : [],
            'currentSeason' => Seasons::current(),
        ]);
    }

    public function create(Request $request): Response
    {
        $ctx = FarmContext::current();
        $fields = $this->fields->forFarm($ctx->farmId());
        if ($fields === []) {
            Flash::info('Add a field first — every crop planting belongs to a field.');
            return $this->redirect(url('fields/create'));
        }

        return $this->view('crops/form', [
            'title'         => 'Add crop planting',
            'active'        => 'crops',
            'crop'          => null,
            'fields'        => $fields,
            'cropTypes'     => $this->cropTypes->available($ctx->farmId()),
            'currentSeason' => Seasons::current(),
        ]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $prepared = $this->prepare($request, null);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $id = $this->crops->create($ctx->farmId(), $prepared);
        AuditLog::user('crop.created', (string) Auth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Crop planting recorded.');
        return $this->redirect(url('crops/' . $id));
    }

    public function show(Request $request): Response
    {
        $ctx = FarmContext::current();
        $crop = $this->crops->find($ctx->farmId(), (string) $request->route('id'));
        if ($crop === null) {
            Flash::error('Crop planting not found.');
            return $this->redirect(url('crops'));
        }

        return $this->view('crops/show', [
            'title'     => (string) $crop['crop_name'] . ' — ' . (string) $crop['field_name'],
            'active'    => 'crops',
            'crop'      => $crop,
            'timeline'  => CropTimeline::forPlanting((string) $crop['crop_name'], (string) $crop['planting_date']),
            'canManage' => $ctx->can('crops.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $crop = $this->crops->find($ctx->farmId(), (string) $request->route('id'));
        if ($crop === null) {
            Flash::error('Crop planting not found.');
            return $this->redirect(url('crops'));
        }

        return $this->view('crops/form', [
            'title'         => 'Edit crop planting',
            'active'        => 'crops',
            'crop'          => $crop,
            'fields'        => $this->fields->forFarm($ctx->farmId()),
            'cropTypes'     => $this->cropTypes->available($ctx->farmId()),
            'currentSeason' => Seasons::current(),
        ]);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->crops->find($ctx->farmId(), $id) === null) {
            Flash::error('Crop planting not found.');
            return $this->redirect(url('crops'));
        }

        $prepared = $this->prepare($request, $id);
        if ($prepared instanceof Response) {
            return $prepared;
        }
        unset($prepared['field_id']); // field is fixed after creation

        $this->crops->update($ctx->farmId(), $id, $prepared);
        AuditLog::user('crop.updated', (string) Auth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Crop planting updated.');
        return $this->redirect(url('crops/' . $id));
    }

    public function archive(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->crops->find($ctx->farmId(), $id) === null) {
            Flash::error('Crop planting not found.');
            return $this->redirect(url('crops'));
        }

        $reason = trim((string) $request->input('reason', ''));
        $this->crops->archive($ctx->farmId(), $id, mb_substr($reason, 0, 255));
        AuditLog::user('crop.archived', (string) Auth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Crop planting archived.');
        return $this->redirect(url('crops'));
    }

    public function restore(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->crops->find($ctx->farmId(), $id) === null) {
            Flash::error('Crop planting not found.');
            return $this->redirect(url('crops'));
        }
        $this->crops->restore($ctx->farmId(), $id);
        AuditLog::user('crop.restored', (string) Auth::id(), ['crop_field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Crop planting restored.');
        return $this->redirect(url('crops?view=archived'));
    }

    /**
     * @param string|null $excludeId the planting's own id when editing it, so its
     *   current area doesn't count against itself in the remaining-acreage check
     * @return array<string,mixed>|Response
     */
    private function prepare(Request $request, ?string $excludeId): array|Response
    {
        $ctx = FarmContext::current();

        $data = $this->validate($request, [
            'field_id'              => ['required'],
            'crop_type'             => ['required', 'max:120'],
            'variety'               => ['max:120'],
            'area_planted'          => ['required', 'numeric', 'min:0', 'max:100000'],
            'season'                => ['required', 'max:60'],
            'planting_date'         => ['required', 'date'],
            'expected_harvest_date' => ['required', 'date'],
            'status'                => ['required', 'in:Active,Harvested,Failed,Terminated'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        // The field must belong to this farm.
        $field = $this->fields->find($ctx->farmId(), (string) $data['field_id']);
        if ($field === null) {
            return $this->fieldError($request, 'field_id', 'Choose one of your fields.');
        }

        $plant = date('Y-m-d', (int) strtotime((string) $data['planting_date']));
        $harvest = date('Y-m-d', (int) strtotime((string) $data['expected_harvest_date']));
        if ($harvest < $plant) {
            return $this->fieldError($request, 'expected_harvest_date', 'Expected harvest cannot be before planting.');
        }

        // A planting only claims land while it's Active — one still growing
        // (or just recorded) can't claim more than the field actually has
        // left once every other active planting on it is accounted for.
        $areaPlanted = (float) $data['area_planted'];
        if ((string) $data['status'] === 'Active') {
            $allocated = $this->crops->activeAllocatedArea($ctx->farmId(), (string) $field['id'], $excludeId);
            $remaining = (float) $field['cultivatable_area'] - $allocated;
            if ($areaPlanted > $remaining + 1e-9) {
                return $this->fieldError(
                    $request,
                    'area_planted',
                    sprintf(
                        'Only %.2f ha remaining on %s (%.2f of %.2f ha cultivatable already planted).',
                        max(0.0, $remaining),
                        (string) $field['name'],
                        $allocated,
                        (float) $field['cultivatable_area'],
                    ),
                );
            }
        }

        return [
            'field_id'              => (string) $data['field_id'],
            'crop_type_id'          => $this->cropTypes->resolveOrCreate($ctx->farmId(), (string) $data['crop_type']),
            'variety'               => isset($data['variety']) ? trim((string) $data['variety']) : '',
            'area_planted'          => (float) $data['area_planted'],
            'season'                => trim((string) $data['season']),
            'planting_date'         => $plant,
            'expected_harvest_date' => $harvest,
            'status'                => (string) $data['status'],
        ];
    }
}
