<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\ClimateEventRepository;
use App\Repositories\CropFieldRepository;
use App\Repositories\DocumentRepository;
use App\Services\Upload;
use Throwable;

/** Permission piggybacks on finance.manage/.view, same quirk as the web controller. */
final class ApiClimateEventsController
{
    private const TYPES = ['drought', 'flood', 'wind', 'frost', 'hail', 'other'];
    private const LINKED_TYPE = 'climate_event';

    public function __construct(
        private readonly ClimateEventRepository $events = new ClimateEventRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly DocumentRepository $documents = new DocumentRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::json(['data' => $this->events->forFarm(FarmContext::current()->farmId())]);
    }

    public function show(Request $request): Response
    {
        $event = $this->events->find(FarmContext::current()->farmId(), (string) $request->route('id'));
        if ($event === null) {
            return Response::json(['error' => 'Event not found.'], 404);
        }
        return Response::json(['data' => $event]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $id = $this->events->create($ctx->farmId(), (string) ApiAuth::id(), $data);
        $this->attachEvidence($request, $ctx->farmId(), $id);
        AuditLog::user('api.climate_event.created', (string) ApiAuth::id(), ['event_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->events->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->events->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Event not found.'], 404);
        }

        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }

        $this->events->update($ctx->farmId(), $id, $data);
        $this->attachEvidence($request, $ctx->farmId(), $id);
        AuditLog::user('api.climate_event.updated', (string) ApiAuth::id(), ['event_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->events->find($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->events->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Event not found.'], 404);
        }

        foreach ($this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, $id) as $doc) {
            Upload::delete((string) $doc['asset_id']);
            $this->documents->delete($ctx->farmId(), (string) $doc['id']);
        }
        $this->events->delete($ctx->farmId(), $id);
        AuditLog::user('api.climate_event.deleted', (string) ApiAuth::id(), ['event_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $ctx = FarmContext::current();
        $v = Validator::make($request->all(), [
            'event_type' => ['required', 'in:' . implode(',', self::TYPES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['date'],
            'description' => ['max:500'],
            'estimated_loss_amount' => ['numeric', 'min:0', 'max:1000000000'],
            'affected_crop_field_id' => [],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $cropFieldId = (string) ($d['affected_crop_field_id'] ?? '');
        if ($cropFieldId !== '' && $this->crops->find($ctx->farmId(), $cropFieldId) === null) {
            $cropFieldId = '';
        }

        return [
            'event_type' => (string) $d['event_type'],
            'start_date' => date('Y-m-d', (int) strtotime((string) $d['start_date'])),
            'end_date' => isset($d['end_date']) && $d['end_date'] !== '' ? date('Y-m-d', (int) strtotime((string) $d['end_date'])) : null,
            'description' => isset($d['description']) && $d['description'] !== '' ? trim((string) $d['description']) : null,
            'estimated_loss_amount' => isset($d['estimated_loss_amount']) && $d['estimated_loss_amount'] !== '' ? (float) $d['estimated_loss_amount'] : null,
            'affected_crop_field_id' => $cropFieldId ?: null,
        ];
    }

    /** Optional evidence photo — additive, never blocks saving the event itself. */
    private function attachEvidence(Request $request, string $farmId, string $eventId): void
    {
        $file = $request->file('evidence');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        try {
            $stored = Upload::store($file);
        } catch (Throwable) {
            return;
        }
        $this->documents->create($farmId, (string) ApiAuth::id(), [
            'name' => 'Climate event evidence — ' . date('Y-m-d'),
            'type' => 'photo',
            'asset_id' => $stored['asset_id'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'linked_to' => $eventId,
            'linked_type' => self::LINKED_TYPE,
        ]);
    }
}
