<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class CropFieldRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * @param array{season?:string,field_id?:string,archived?:bool} $filters
     * @return array<int,array<string,mixed>>
     */
    public function forFarm(string $farmId, array $filters = []): array
    {
        $where = ['cf.farm_id = :fid'];
        $bind = ['fid' => $farmId];

        if (!empty($filters['season'])) {
            $where[] = 'cf.season = :season';
            $bind['season'] = $filters['season'];
        }
        if (!empty($filters['field_id'])) {
            $where[] = 'cf.field_id = :field_id';
            $bind['field_id'] = $filters['field_id'];
        }
        $where[] = ($filters['archived'] ?? false) ? 'cf.is_archived = 1' : 'cf.is_archived = 0';

        return $this->db->select(
            'SELECT cf.*, ct.name AS crop_name, f.name AS field_name
             FROM crop_fields cf
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY cf.planting_date DESC, ct.name ASC',
            $bind,
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT cf.*, ct.name AS crop_name, f.name AS field_name
             FROM crop_fields cf
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE cf.id = :id AND cf.farm_id = :fid
             LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countActive(string $farmId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM crop_fields WHERE farm_id = :fid AND is_archived = 0',
            ['fid' => $farmId],
        );
    }

    /** @return list<string> distinct seasons used on this farm, newest-looking first */
    public function seasons(string $farmId): array
    {
        $rows = $this->db->select(
            "SELECT DISTINCT season FROM crop_fields WHERE farm_id = :fid AND season <> '' ORDER BY season DESC",
            ['fid' => $farmId],
        );
        return array_map(static fn (array $r): string => (string) $r['season'], $rows);
    }

    /**
     * @param array{field_id:string,crop_type_id:string,variety:string,area_planted:float,
     *              season:string,planting_date:string,expected_harvest_date:string,status:string} $data
     */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('crop_fields', [
            'id'                    => $id,
            'farm_id'               => $farmId,
            'field_id'              => $data['field_id'],
            'crop_type_id'          => $data['crop_type_id'],
            'variety'               => $data['variety'],
            'area_planted'          => $data['area_planted'],
            'season'                => $data['season'],
            'planting_date'         => $data['planting_date'],
            'expected_harvest_date' => $data['expected_harvest_date'],
            'status'                => $data['status'],
            'is_archived'           => 0,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('crop_fields', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function archive(string $farmId, string $id, string $reason): void
    {
        $this->db->update('crop_fields', [
            'is_archived'     => 1,
            'archived_at'     => Dates::nowUtc(),
            'archived_reason' => $reason !== '' ? $reason : null,
            'updated_at'      => Dates::nowUtc(),
        ], ['id' => $id, 'farm_id' => $farmId]);
    }

    public function restore(string $farmId, string $id): void
    {
        $this->db->update('crop_fields', [
            'is_archived'     => 0,
            'archived_at'     => null,
            'archived_reason' => null,
            'updated_at'      => Dates::nowUtc(),
        ], ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('crop_fields', ['id' => $id, 'farm_id' => $farmId]);
    }
}
