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
        $archived = $request->query('view') === 'archived';

        return $this->view('crops/index', [
            'title'     => 'Crops',
            'active'    => 'crops',
            'crops'     => $this->crops->forFarm($ctx->farmId(), [
                'season'   => $season,
                'archived' => $archived,
            ]),
            'seasons'   => $this->crops->seasons($ctx->farmId()),
            'season'    => $season,
            'archived'  => $archived,
            'canManage' => $ctx->can('crops.manage') && !$ctx->isReadOnly(),
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
        $prepared = $this->prepare($request);
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

        $prepared = $this->prepare($request);
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

    /** @return array<string,mixed>|Response */
    private function prepare(Request $request): array|Response
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
        if ($this->fields->find($ctx->farmId(), (string) $data['field_id']) === null) {
            return $this->fieldError($request, 'field_id', 'Choose one of your fields.');
        }

        $plant = date('Y-m-d', (int) strtotime((string) $data['planting_date']));
        $harvest = date('Y-m-d', (int) strtotime((string) $data['expected_harvest_date']));
        if ($harvest < $plant) {
            return $this->fieldError($request, 'expected_harvest_date', 'Expected harvest cannot be before planting.');
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
