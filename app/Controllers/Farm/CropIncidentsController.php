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
use App\Repositories\CropIncidentRepository;
use App\Repositories\DocumentRepository;
use App\Services\Upload;
use Throwable;

final class CropIncidentsController extends Controller
{
    private const TYPES = ['pest', 'disease', 'other'];
    private const SEVERITIES = ['mild', 'moderate', 'severe'];
    private const STATUSES = ['open', 'treated', 'resolved'];
    private const LINKED_TYPE = 'crop_incident';

    public function __construct(
        private readonly CropIncidentRepository $incidents = new CropIncidentRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly DocumentRepository $documents = new DocumentRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $filters = [
            'type'   => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ];
        $rows = $this->incidents->forFarm($ctx->farmId(), $filters);

        return $this->view('incidents/index', [
            'title'       => 'Pest & Disease Log',
            'active'      => 'incidents',
            'rows'        => $rows,
            'plantings'   => $this->crops->forFarm($ctx->farmId()),
            'types'       => self::TYPES,
            'severities'  => self::SEVERITIES,
            'statuses'    => self::STATUSES,
            'filters'     => $filters,
            'mediaByRow'  => $this->documents->forLinkedMany($ctx->farmId(), self::LINKED_TYPE, array_map(static fn ($r) => (string) $r['id'], $rows)),
            'canManage'   => $ctx->can('crops.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function create(Request $request): Response
    {
        $ctx = FarmContext::current();
        $plantings = $this->crops->forFarm($ctx->farmId());
        if ($plantings === []) {
            Flash::info('Add a crop planting first.');
            return $this->redirect(url('crops/create'));
        }
        return $this->form($ctx, null);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        $prepared = $this->prepare($request, isEdit: false);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $id = $this->incidents->create($ctx->farmId(), (string) Auth::id(), $prepared);
        $this->attachMedia($request, $ctx->farmId(), $id);

        AuditLog::user('incident.created', (string) Auth::id(), ['incident_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Incident logged.');
        return $this->redirect(url('incidents/' . $id));
    }

    public function show(Request $request): Response
    {
        $ctx = FarmContext::current();
        $incident = $this->incidents->find($ctx->farmId(), (string) $request->route('id'));
        if ($incident === null) {
            Flash::error('Incident not found.');
            return $this->redirect(url('incidents'));
        }
        return $this->view('incidents/show', [
            'title'      => ucfirst((string) $incident['type']) . ' — ' . (string) $incident['crop_name'],
            'active'     => 'incidents',
            'i'          => $incident,
            'media'      => $this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, (string) $incident['id']),
            'plantings'  => $this->crops->forFarm($ctx->farmId()),
            'types'      => self::TYPES,
            'severities' => self::SEVERITIES,
            'statuses'   => self::STATUSES,
            'canManage'  => $ctx->can('crops.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $incident = $this->incidents->find($ctx->farmId(), (string) $request->route('id'));
        if ($incident === null) {
            Flash::error('Incident not found.');
            return $this->redirect(url('incidents'));
        }
        return $this->form($ctx, $incident);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->incidents->find($ctx->farmId(), $id) === null) {
            Flash::error('Incident not found.');
            return $this->redirect(url('incidents'));
        }

        $prepared = $this->prepare($request, isEdit: true);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $this->incidents->update($ctx->farmId(), $id, $prepared);
        $this->attachMedia($request, $ctx->farmId(), $id);

        AuditLog::user('incident.updated', (string) Auth::id(), ['incident_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Incident updated.');
        return $this->redirect(url('incidents/' . $id));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->incidents->find($ctx->farmId(), $id) === null) {
            Flash::error('Incident not found.');
            return $this->redirect(url('incidents'));
        }
        foreach ($this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, $id) as $doc) {
            Upload::delete((string) $doc['asset_id']);
            $this->documents->delete($ctx->farmId(), (string) $doc['id']);
        }
        $this->incidents->delete($ctx->farmId(), $id);
        AuditLog::user('incident.deleted', (string) Auth::id(), ['incident_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Incident deleted.');
        return $this->redirect(url('incidents'));
    }

    /* ---------------------------------------------------------------- helpers */

    private function form(FarmContext $ctx, ?array $incident): Response
    {
        return $this->view('incidents/form', [
            'title'      => $incident === null ? 'Report an incident' : 'Edit incident',
            'active'     => 'incidents',
            'i'          => $incident,
            'plantings'  => $this->crops->forFarm($ctx->farmId()),
            'types'      => self::TYPES,
            'severities' => self::SEVERITIES,
            'statuses'   => self::STATUSES,
            'media'      => $incident === null ? [] : $this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, (string) $incident['id']),
        ]);
    }

    /** @return array<string,mixed>|Response */
    private function prepare(Request $request, bool $isEdit): array|Response
    {
        $ctx = FarmContext::current();

        $rules = [
            'crop_field_id'   => ['required'],
            'type'            => ['required', 'in:' . implode(',', self::TYPES)],
            'description'     => ['required', 'max:500'],
            'severity'        => ['required', 'in:' . implode(',', self::SEVERITIES)],
            'reported_date'   => ['required', 'date'],
            'treatment_notes' => ['max:1000'],
            'referred_to'     => ['max:160'],
        ];
        if ($isEdit) {
            $rules['status'] = ['required', 'in:' . implode(',', self::STATUSES)];
        }

        $data = $this->validate($request, $rules);
        if ($data instanceof Response) {
            return $data;
        }

        if ($this->crops->find($ctx->farmId(), (string) $data['crop_field_id']) === null) {
            return $this->fieldError($request, 'crop_field_id', 'Choose one of your crop plantings.');
        }

        return [
            'crop_field_id'   => (string) $data['crop_field_id'],
            'type'            => (string) $data['type'],
            'description'     => trim((string) $data['description']),
            'severity'        => (string) $data['severity'],
            'status'          => $isEdit ? (string) $data['status'] : 'open',
            'reported_date'   => date('Y-m-d', (int) strtotime((string) $data['reported_date'])),
            'treatment_notes' => trim((string) ($data['treatment_notes'] ?? '')) ?: null,
            'referred_to'     => trim((string) ($data['referred_to'] ?? '')) ?: null,
        ];
    }

    /** Optional photo/voice-note attachment — additive, never replaces an existing one. */
    private function attachMedia(Request $request, string $farmId, string $incidentId): void
    {
        $file = $request->file('attachment');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        try {
            $stored = Upload::store($file);
        } catch (Throwable $e) {
            Flash::error('Incident saved, but the attachment failed: ' . $e->getMessage());
            return;
        }
        $this->documents->create($farmId, (string) Auth::id(), [
            'name'        => 'Incident attachment — ' . date('Y-m-d'),
            'type'        => str_starts_with($stored['mime_type'], 'audio/') ? 'other' : 'photo',
            'asset_id'    => $stored['asset_id'],
            'mime_type'   => $stored['mime_type'],
            'size'        => $stored['size'],
            'linked_to'   => $incidentId,
            'linked_type' => self::LINKED_TYPE,
        ]);
    }
}
