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
use App\Repositories\FieldRepository;

final class FieldsController extends Controller
{
    public function __construct(
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('fields/index', [
            'title'     => 'Fields',
            'active'    => 'fields',
            'fields'    => $this->fields->forFarm($ctx->farmId()),
            'canManage' => $ctx->can('fields.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->view('fields/form', [
            'title'  => 'Add field',
            'active' => 'fields',
            'field'  => null,
        ]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $id = $this->fields->create($ctx->farmId(), $data);
        AuditLog::user('field.created', (string) Auth::id(), ['field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Field added.');
        return $this->redirect(url('fields'));
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $field = $this->fields->find($ctx->farmId(), (string) $request->route('id'));
        if ($field === null) {
            Flash::error('Field not found.');
            return $this->redirect(url('fields'));
        }
        return $this->view('fields/form', [
            'title'  => 'Edit field',
            'active' => 'fields',
            'field'  => $field,
        ]);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->fields->find($ctx->farmId(), $id) === null) {
            Flash::error('Field not found.');
            return $this->redirect(url('fields'));
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $this->fields->update($ctx->farmId(), $id, $data);
        AuditLog::user('field.updated', (string) Auth::id(), ['field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Field updated.');
        return $this->redirect(url('fields'));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');

        $field = $this->fields->find($ctx->farmId(), $id);
        if ($field === null) {
            Flash::error('Field not found.');
            return $this->redirect(url('fields'));
        }
        if ($this->crops->forFarm($ctx->farmId(), ['field_id' => $id]) !== []) {
            Flash::error('Remove or reassign this field’s crop plantings before deleting it.');
            return $this->redirect(url('fields'));
        }

        $this->fields->delete($ctx->farmId(), $id);
        AuditLog::user('field.deleted', (string) Auth::id(), ['field_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Field deleted.');
        return $this->redirect(url('fields'));
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $data = $this->validate($request, [
            'name'              => ['required', 'max:160'],
            'total_area'        => ['required', 'numeric', 'min:0', 'max:100000'],
            'cultivatable_area' => ['required', 'numeric', 'min:0', 'max:100000'],
            'soil_type'         => ['required', 'max:80'],
            'location_lat'      => ['numeric'],
            'location_lng'      => ['numeric'],
            'notes'             => ['max:2000'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $total = (float) $data['total_area'];
        $cult = (float) $data['cultivatable_area'];
        if ($cult > $total) {
            return $this->fieldError($request, 'cultivatable_area', 'Cultivatable area cannot exceed total area.');
        }

        return [
            'name'              => trim((string) $data['name']),
            'total_area'        => $total,
            'cultivatable_area' => $cult,
            'soil_type'         => trim((string) $data['soil_type']),
            'location_lat'      => isset($data['location_lat']) && $data['location_lat'] !== '' ? (float) $data['location_lat'] : null,
            'location_lng'      => isset($data['location_lng']) && $data['location_lng'] !== '' ? (float) $data['location_lng'] : null,
            'notes'             => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ];
    }
}
