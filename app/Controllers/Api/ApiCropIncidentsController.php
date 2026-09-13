<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CropFieldRepository;
use App\Repositories\CropIncidentRepository;
use App\Repositories\DocumentRepository;
use App\Services\Upload;
use Throwable;

final class ApiCropIncidentsController
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
        $farmId = FarmContext::current()->farmId();
        $filters = [
            'type'   => (string) $request->query('type', ''),
            'status' => (string) $request->query('status', ''),
        ];
        return Response::json(['data' => $this->incidents->forFarm($farmId, $filters)]);
    }

    public function show(Request $request): Response
    {
        $farmId = FarmContext::current()->farmId();
        $incident = $this->incidents->find($farmId, (string) $request->route('id'));
        if ($incident === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }
        $incident['media'] = $this->documents->forLinked($farmId, self::LINKED_TYPE, (string) $incident['id']);
        return Response::json(['data' => $incident]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $prepared = $this->prepare($request, isEdit: false);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $id = $this->incidents->create($ctx->farmId(), (string) ApiAuth::id(), $prepared);
        $warning = $this->attachMedia($request, $ctx->farmId(), $id);

        AuditLog::user('api.incident.created', (string) ApiAuth::id(), ['incident_id' => $id], $ctx->farmId(), $request->ip());
        $incident = $this->incidents->find($ctx->farmId(), $id);
        if ($incident !== null) {
            $incident['media'] = $this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, $id);
        }
        $payload = ['data' => $incident];
        if ($warning !== null) {
            $payload['attachment_warning'] = $warning;
        }
        return Response::json($payload, 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->incidents->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $prepared = $this->prepare($request, isEdit: true);
        if ($prepared instanceof Response) {
            return $prepared;
        }

        $this->incidents->update($ctx->farmId(), $id, $prepared);
        $warning = $this->attachMedia($request, $ctx->farmId(), $id);

        AuditLog::user('api.incident.updated', (string) ApiAuth::id(), ['incident_id' => $id], $ctx->farmId(), $request->ip());
        $incident = $this->incidents->find($ctx->farmId(), $id);
        if ($incident !== null) {
            $incident['media'] = $this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, $id);
        }
        $payload = ['data' => $incident];
        if ($warning !== null) {
            $payload['attachment_warning'] = $warning;
        }
        return Response::json($payload);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('crops.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->incidents->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        foreach ($this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, $id) as $doc) {
            Upload::delete((string) $doc['asset_id']);
            $this->documents->delete($ctx->farmId(), (string) $doc['id']);
        }
        $this->incidents->delete($ctx->farmId(), $id);
        AuditLog::user('api.incident.deleted', (string) ApiAuth::id(), ['incident_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
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

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        if ($this->crops->find($ctx->farmId(), (string) $d['crop_field_id']) === null) {
            return Response::json(['errors' => ['crop_field_id' => ['Choose one of your crop plantings.']]], 422);
        }

        return [
            'crop_field_id'   => (string) $d['crop_field_id'],
            'type'            => (string) $d['type'],
            'description'     => trim((string) $d['description']),
            'severity'        => (string) $d['severity'],
            'status'          => $isEdit ? (string) $d['status'] : 'open',
            'reported_date'   => date('Y-m-d', (int) strtotime((string) $d['reported_date'])),
            'treatment_notes' => trim((string) ($d['treatment_notes'] ?? '')) ?: null,
            'referred_to'     => trim((string) ($d['referred_to'] ?? '')) ?: null,
        ];
    }

    /**
     * Optional photo/voice-note attachment — additive, never replaces an
     * existing one. Non-fatal: the incident itself is already saved either
     * way; a failure here is surfaced to the (JSON) caller as a warning
     * string rather than silently dropped, since there's no flash-message
     * channel for an API client to see it otherwise.
     */
    private function attachMedia(Request $request, string $farmId, string $incidentId): ?string
    {
        $file = $request->file('attachment');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        try {
            $stored = Upload::store($file);
        } catch (Throwable $e) {
            return 'Incident saved, but the attachment failed: ' . $e->getMessage();
        }
        $this->documents->create($farmId, (string) ApiAuth::id(), [
            'name'        => 'Incident attachment — ' . date('Y-m-d'),
            'type'        => str_starts_with($stored['mime_type'], 'audio/') ? 'other' : 'photo',
            'asset_id'    => $stored['asset_id'],
            'mime_type'   => $stored['mime_type'],
            'size'        => $stored['size'],
            'linked_to'   => $incidentId,
            'linked_type' => self::LINKED_TYPE,
        ]);
        return null;
    }
}
