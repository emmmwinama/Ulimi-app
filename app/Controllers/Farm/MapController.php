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
use App\Repositories\FieldRepository;
use App\Repositories\GisRepository;

final class MapController extends Controller
{
    private const MARKER_TYPES = ['borehole', 'irrigation', 'shed', 'road', 'gate', 'store', 'other'];
    private const ZONE_TYPES = ['management', 'soil', 'irrigation', 'problem', 'other'];

    public function __construct(
        private readonly GisRepository $gis = new GisRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
    ) {
    }

    /* -------------------------------------------------------------- farm map */

    public function farmMap(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('map/farm', [
            'title'     => 'Farm map',
            'active'    => 'map',
            'data'      => $this->gis->farmMapData($ctx->farmId()),
            'farm'      => $ctx->farm,
            'markerTypes' => self::MARKER_TYPES,
            'fields'    => $this->fields->forFarm($ctx->farmId()),
            'canManage' => $ctx->can('fields.manage') && !$ctx->isReadOnly(),
        ]);
    }

    /* ------------------------------------------------------------ field map */

    public function fieldMap(Request $request): Response
    {
        $ctx = FarmContext::current();
        $field = $this->fields->find($ctx->farmId(), (string) $request->route('id'));
        if ($field === null) {
            Flash::error('Field not found.');
            return $this->redirect(url('fields'));
        }
        return $this->view('map/field', [
            'title'     => (string) $field['name'] . ' — map',
            'active'    => 'fields',
            'field'     => $field,
            'boundary'  => $this->gis->boundaryForField($ctx->farmId(), (string) $field['id']),
            'zones'     => $this->gis->zonesForField($ctx->farmId(), (string) $field['id']),
            'zoneTypes' => self::ZONE_TYPES,
            'canManage' => $ctx->can('fields.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function saveBoundary(Request $request): Response
    {
        $ctx = FarmContext::current();
        $fieldId = (string) $request->route('id');
        if ($this->fields->find($ctx->farmId(), $fieldId) === null) {
            return Response::json(['error' => 'Field not found.'], 404);
        }

        $geometry = $this->geometryFromInput($request);
        if ($geometry === null) {
            return $this->fieldError($request, 'boundary', 'Draw a valid area on the map first.');
        }

        $this->gis->saveBoundary($ctx->farmId(), $fieldId, $geometry);
        AuditLog::user('gis.boundary_saved', (string) Auth::id(), ['field_id' => $fieldId], $ctx->farmId(), $request->ip());
        Flash::success('Field boundary saved.');
        return $this->redirect(url('fields/' . rawurlencode($fieldId) . '/map'));
    }

    public function deleteBoundary(Request $request): Response
    {
        $ctx = FarmContext::current();
        $fieldId = (string) $request->route('id');
        $this->gis->deleteBoundary($ctx->farmId(), $fieldId);
        Flash::success('Boundary removed.');
        return $this->redirect(url('fields/' . rawurlencode($fieldId) . '/map'));
    }

    public function addZone(Request $request): Response
    {
        $ctx = FarmContext::current();
        $fieldId = (string) $request->route('id');

        $boundary = $this->gis->boundaryForField($ctx->farmId(), $fieldId);
        if ($boundary === null) {
            Flash::error('Draw the field boundary before adding zones.');
            return $this->redirect(url('fields/' . rawurlencode($fieldId) . '/map'));
        }

        $data = $this->validate($request, [
            'name' => ['required', 'max:120'],
            'type' => ['required', 'in:' . implode(',', self::ZONE_TYPES)],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $geometry = $this->geometryFromInput($request);
        if ($geometry === null) {
            return $this->fieldError($request, 'zone', 'Draw the zone on the map first.');
        }

        $colour = (string) $request->input('colour', '');
        $this->gis->addZone(
            $ctx->farmId(),
            $fieldId,
            (string) $boundary['id'],
            trim((string) $data['name']),
            (string) $data['type'],
            $geometry,
            preg_match('/^#[0-9a-f]{6}$/i', $colour) === 1 ? $colour : null,
        );
        Flash::success('Zone added.');
        return $this->redirect(url('fields/' . rawurlencode($fieldId) . '/map'));
    }

    public function deleteZone(Request $request): Response
    {
        $ctx = FarmContext::current();
        $this->gis->deleteZone($ctx->farmId(), (string) $request->route('zoneId'));
        Flash::success('Zone removed.');
        return $this->redirect(url('fields/' . rawurlencode((string) $request->route('id')) . '/map'));
    }

    /* ------------------------------------------------------------- markers */

    public function addMarker(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'type'  => ['required', 'in:' . implode(',', self::MARKER_TYPES)],
            'label' => ['required', 'max:120'],
            'lat'   => ['required', 'numeric'],
            'lng'   => ['required', 'numeric'],
            'notes' => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return $this->fieldError($request, 'lat', 'That location is off the map.');
        }

        $fieldId = (string) $request->input('field_id', '');
        if ($fieldId !== '' && $this->fields->find($ctx->farmId(), $fieldId) === null) {
            $fieldId = '';
        }

        $this->gis->addMarker($ctx->farmId(), [
            'field_id' => $fieldId ?: null,
            'type'     => (string) $data['type'],
            'label'    => trim((string) $data['label']),
            'lat'      => $lat,
            'lng'      => $lng,
            'notes'    => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ]);
        AuditLog::user('gis.marker_added', (string) Auth::id(), [], $ctx->farmId(), $request->ip());
        Flash::success('Marker added.');
        return $this->redirect(url('map'));
    }

    public function deleteMarker(Request $request): Response
    {
        $ctx = FarmContext::current();
        $this->gis->deleteMarker($ctx->farmId(), (string) $request->route('markerId'));
        Flash::success('Marker removed.');
        return $this->redirect(url('map'));
    }

    /* ---------------------------------------------------------------- helper */

    /**
     * Parse the posted `geometry` field (a GeoJSON geometry string from the
     * Leaflet draw handler). Returns the geometry array or null when invalid.
     *
     * @return array<mixed>|null
     */
    private function geometryFromInput(Request $request): ?array
    {
        $raw = (string) $request->input('geometry', '');
        if ($raw === '' || strlen($raw) > 200_000) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }
        $type = $decoded['type'] ?? null;
        if (!in_array($type, ['Polygon', 'MultiPolygon'], true) || !isset($decoded['coordinates'])) {
            return null;
        }
        // Sanity: at least one ring with 3+ points.
        $ring = $decoded['coordinates'][0] ?? null;
        if (!is_array($ring) || ($type === 'MultiPolygon' ? count($ring[0] ?? []) : count($ring)) < 3) {
            return null;
        }
        return $decoded;
    }
}
