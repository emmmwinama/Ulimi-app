<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Geo;
use App\Support\Ulid;

final class GisRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /* ---------------------------------------------------------- boundaries */

    /** @return array<string,mixed>|null */
    public function boundaryForField(string $farmId, string $fieldId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM field_boundaries WHERE farm_id = :fid AND field_id = :field LIMIT 1',
            ['fid' => $farmId, 'field' => $fieldId],
        );
    }

    /** @return array<int,array<string,mixed>> every boundary on the farm, with field name */
    public function boundaries(string $farmId): array
    {
        return $this->db->select(
            'SELECT b.*, f.name AS field_name
             FROM field_boundaries b JOIN fields f ON f.id = b.field_id
             WHERE b.farm_id = :fid',
            ['fid' => $farmId],
        );
    }

    /**
     * Upsert a field's boundary from a GeoJSON geometry array. Area + centroid
     * are computed server-side (never trusted from the client).
     *
     * @param array<mixed> $geometry
     */
    public function saveBoundary(string $farmId, string $fieldId, array $geometry): void
    {
        $areaHa = Geo::areaHa($geometry);
        $centroid = Geo::centroid($geometry);
        $json = json_encode($geometry, JSON_UNESCAPED_SLASHES);
        $now = Dates::nowUtc();

        $existing = $this->boundaryForField($farmId, $fieldId);
        if ($existing !== null) {
            $this->db->update('field_boundaries', [
                'geo_json'     => $json,
                'area_ha'      => $areaHa,
                'centroid_lat' => $centroid[0] ?? null,
                'centroid_lng' => $centroid[1] ?? null,
                'updated_at'   => $now,
            ], ['id' => $existing['id'], 'farm_id' => $farmId]);
            return;
        }

        $this->db->insert('field_boundaries', [
            'id'           => Ulid::generate(),
            'farm_id'      => $farmId,
            'field_id'     => $fieldId,
            'geo_json'     => $json,
            'area_ha'      => $areaHa,
            'centroid_lat' => $centroid[0] ?? null,
            'centroid_lng' => $centroid[1] ?? null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    public function deleteBoundary(string $farmId, string $fieldId): int
    {
        return $this->db->delete('field_boundaries', ['farm_id' => $farmId, 'field_id' => $fieldId]);
    }

    /* --------------------------------------------------------------- zones */

    /** @return array<int,array<string,mixed>> */
    public function zonesForField(string $farmId, string $fieldId): array
    {
        return $this->db->select(
            'SELECT * FROM field_zones WHERE farm_id = :fid AND field_id = :field ORDER BY name ASC',
            ['fid' => $farmId, 'field' => $fieldId],
        );
    }

    /** @param array<mixed> $geometry */
    public function addZone(string $farmId, string $fieldId, string $boundaryId, string $name, string $type, array $geometry, ?string $colour): string
    {
        $id = Ulid::generate();
        $this->db->insert('field_zones', [
            'id'            => $id,
            'farm_id'       => $farmId,
            'boundary_id'   => $boundaryId,
            'field_id'      => $fieldId,
            'crop_field_id' => null,
            'name'          => $name,
            'type'          => $type,
            'geo_json'      => json_encode($geometry, JSON_UNESCAPED_SLASHES),
            'area_ha'       => Geo::areaHa($geometry),
            'colour'        => $colour,
            'notes'         => null,
            'created_at'    => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function deleteZone(string $farmId, string $id): int
    {
        return $this->db->delete('field_zones', ['id' => $id, 'farm_id' => $farmId]);
    }

    /* ------------------------------------------------------------- markers */

    /** @return array<int,array<string,mixed>> */
    public function markers(string $farmId): array
    {
        return $this->db->select(
            'SELECT * FROM farm_markers WHERE farm_id = :fid ORDER BY type ASC, label ASC',
            ['fid' => $farmId],
        );
    }

    /** @param array{field_id:?string,type:string,label:string,lat:float,lng:float,notes:?string} $data */
    public function addMarker(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('farm_markers', [
            'id'         => $id,
            'farm_id'    => $farmId,
            'field_id'   => $data['field_id'],
            'type'       => $data['type'],
            'label'      => $data['label'],
            'lat'        => $data['lat'],
            'lng'        => $data['lng'],
            'notes'      => $data['notes'],
            'icon'       => null,
            'created_at' => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function deleteMarker(string $farmId, string $id): int
    {
        return $this->db->delete('farm_markers', ['id' => $id, 'farm_id' => $farmId]);
    }

    /** Full farm map payload: boundaries, zones, markers, field centres. */
    public function farmMapData(string $farmId): array
    {
        return [
            'boundaries' => $this->boundaries($farmId),
            'zones'      => $this->db->select(
                'SELECT z.*, f.name AS field_name FROM field_zones z JOIN fields f ON f.id = z.field_id WHERE z.farm_id = :fid',
                ['fid' => $farmId],
            ),
            'markers'    => $this->markers($farmId),
            'fields'     => $this->db->select(
                'SELECT id, name, location_lat, location_lng FROM fields
                 WHERE farm_id = :fid AND location_lat IS NOT NULL',
                ['fid' => $farmId],
            ),
        ];
    }
}
