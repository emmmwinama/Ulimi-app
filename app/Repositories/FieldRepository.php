<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * All queries are scoped by farm_id supplied by the caller (from FarmContext,
 * never from request input).
 */
final class FieldRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId): array
    {
        return $this->db->select(
            'SELECT f.*,
                    (SELECT COUNT(*) FROM crop_fields cf
                       WHERE cf.field_id = f.id AND cf.is_archived = 0) AS active_crops,
                    (SELECT GROUP_CONCAT(DISTINCT ct.name ORDER BY ct.name SEPARATOR ", ")
                       FROM crop_fields cf2
                       JOIN crop_types ct ON ct.id = cf2.crop_type_id
                       WHERE cf2.field_id = f.id AND cf2.is_archived = 0) AS crop_names
             FROM fields f
             WHERE f.farm_id = :fid
             ORDER BY f.name ASC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM fields WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countForFarm(string $farmId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM fields WHERE farm_id = :fid', ['fid' => $farmId]);
    }

    public function totalArea(string $farmId): float
    {
        return (float) $this->db->scalar(
            'SELECT COALESCE(SUM(total_area), 0) FROM fields WHERE farm_id = :fid',
            ['fid' => $farmId],
        );
    }

    /**
     * @param array{name:string,total_area:float,cultivatable_area:float,soil_type:string,
     *              location_lat:?float,location_lng:?float,notes:?string} $data
     */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('fields', [
            'id'                => $id,
            'farm_id'           => $farmId,
            'name'              => $data['name'],
            'total_area'        => $data['total_area'],
            'cultivatable_area' => $data['cultivatable_area'],
            'soil_type'         => $data['soil_type'],
            'location_lat'      => $data['location_lat'],
            'location_lng'      => $data['location_lng'],
            'notes'             => $data['notes'],
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('fields', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('fields', ['id' => $id, 'farm_id' => $farmId]);
    }
}
