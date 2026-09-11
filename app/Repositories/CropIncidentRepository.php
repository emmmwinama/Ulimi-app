<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class CropIncidentRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * @param array{type?:string,status?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function forFarm(string $farmId, array $filters = []): array
    {
        $where = ['ci.farm_id = :fid'];
        $bind = ['fid' => $farmId];

        if (!empty($filters['type'])) {
            $where[] = 'ci.type = :type';
            $bind['type'] = $filters['type'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'ci.status = :status';
            $bind['status'] = $filters['status'];
        }

        return $this->db->select(
            'SELECT ci.*, ct.name AS crop_name, f.name AS field_name
             FROM crop_incidents ci
             JOIN crop_fields cf ON cf.id = ci.crop_field_id
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY ci.reported_date DESC, ci.created_at DESC',
            $bind,
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT ci.*, ct.name AS crop_name, f.name AS field_name
             FROM crop_incidents ci
             JOIN crop_fields cf ON cf.id = ci.crop_field_id
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE ci.id = :id AND ci.farm_id = :fid
             LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, string $userId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('crop_incidents', [
            'id'              => $id,
            'farm_id'         => $farmId,
            'crop_field_id'   => $data['crop_field_id'],
            'type'            => $data['type'],
            'description'     => $data['description'],
            'severity'        => $data['severity'],
            'status'          => $data['status'],
            'reported_date'   => $data['reported_date'],
            'treatment_notes' => $data['treatment_notes'],
            'referred_to'     => $data['referred_to'],
            'created_by_id'   => $userId,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('crop_incidents', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('crop_incidents', ['id' => $id, 'farm_id' => $farmId]);
    }
}
