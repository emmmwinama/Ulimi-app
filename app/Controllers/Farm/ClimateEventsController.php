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
use App\Repositories\ClimateEventRepository;
use App\Repositories\CropFieldRepository;
use App\Repositories\DocumentRepository;
use App\Services\Upload;
use Throwable;

final class ClimateEventsController extends Controller
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
        $ctx = FarmContext::current();
        $rows = $this->events->forFarm($ctx->farmId());
        return $this->view('climate-events/index', [
            'title'       => 'Climate Events',
            'active'      => 'climate-events',
            'rows'        => $rows,
            'types'       => self::TYPES,
            'plantings'   => $this->crops->forFarm($ctx->farmId()),
            'mediaByEvent'=> $this->documents->forLinkedMany($ctx->farmId(), self::LINKED_TYPE, array_map(static fn ($r) => (string) $r['id'], $rows)),
            'canManage'   => $ctx->can('finance.manage') && !$ctx->isReadOnly(),
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
        $id = $this->events->create($ctx->farmId(), (string) Auth::id(), $data);
        $this->attachEvidence($request, $ctx->farmId(), $id);
        AuditLog::user('climate_event.created', (string) Auth::id(), ['event_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Climate event recorded.');
        return $this->redirect(url('climate-events'));
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        $row = $this->events->find($ctx->farmId(), (string) $request->route('id'));
        if ($row === null) {
            Flash::error('Event not found.');
            return $this->redirect(url('climate-events'));
        }
        return $this->form($row);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->events->find($ctx->farmId(), $id) === null) {
            Flash::error('Event not found.');
            return $this->redirect(url('climate-events'));
        }
        $data = $this->validated($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->events->update($ctx->farmId(), $id, $data);
        $this->attachEvidence($request, $ctx->farmId(), $id);
        AuditLog::user('climate_event.updated', (string) Auth::id(), ['event_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Climate event updated.');
        return $this->redirect(url('climate-events'));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->events->find($ctx->farmId(), $id) === null) {
            Flash::error('Event not found.');
            return $this->redirect(url('climate-events'));
        }
        foreach ($this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, $id) as $doc) {
            Upload::delete((string) $doc['asset_id']);
            $this->documents->delete($ctx->farmId(), (string) $doc['id']);
        }
        $this->events->delete($ctx->farmId(), $id);
        AuditLog::user('climate_event.deleted', (string) Auth::id(), ['event_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Climate event deleted.');
        return $this->redirect(url('climate-events'));
    }

    /* ---------------------------------------------------------------- helpers */

    private function form(?array $event): Response
    {
        $ctx = FarmContext::current();
        return $this->view('climate-events/form', [
            'title'     => $event === null ? 'Record a climate event' : 'Edit climate event',
            'active'    => 'climate-events',
            'e'         => $event,
            'types'     => self::TYPES,
            'plantings' => $this->crops->forFarm($ctx->farmId()),
            'media'     => $event === null ? [] : $this->documents->forLinked($ctx->farmId(), self::LINKED_TYPE, (string) $event['id']),
        ]);
    }

    /** @return array<string,mixed>|Response */
    private function validated(Request $request): array|Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'event_type'             => ['required', 'in:' . implode(',', self::TYPES)],
            'start_date'              => ['required', 'date'],
            'end_date'                => ['date'],
            'description'             => ['max:500'],
            'estimated_loss_amount'   => ['numeric', 'min:0', 'max:1000000000'],
            'affected_crop_field_id'  => [],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $cropFieldId = (string) ($data['affected_crop_field_id'] ?? '');
        if ($cropFieldId !== '' && $this->crops->find($ctx->farmId(), $cropFieldId) === null) {
            $cropFieldId = '';
        }

        return [
            'event_type'             => (string) $data['event_type'],
            'start_date'             => date('Y-m-d', (int) strtotime((string) $data['start_date'])),
            'end_date'               => isset($data['end_date']) && $data['end_date'] !== '' ? date('Y-m-d', (int) strtotime((string) $data['end_date'])) : null,
            'description'            => isset($data['description']) && $data['description'] !== '' ? trim((string) $data['description']) : null,
            'estimated_loss_amount'  => isset($data['estimated_loss_amount']) && $data['estimated_loss_amount'] !== '' ? (float) $data['estimated_loss_amount'] : null,
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
        } catch (Throwable $e) {
            Flash::error('Event saved, but the evidence upload failed: ' . $e->getMessage());
            return;
        }
        $this->documents->create($farmId, (string) Auth::id(), [
            'name'        => 'Climate event evidence — ' . date('Y-m-d'),
            'type'        => 'photo',
            'asset_id'    => $stored['asset_id'],
            'mime_type'   => $stored['mime_type'],
            'size'        => $stored['size'],
            'linked_to'   => $eventId,
            'linked_type' => self::LINKED_TYPE,
        ]);
    }
}
