<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class ClimateEventRepository
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
            'SELECT ce.*, ct.name AS crop_name, f.name AS field_name
             FROM climate_events ce
             LEFT JOIN crop_fields cf ON cf.id = ce.affected_crop_field_id
             LEFT JOIN crop_types ct ON ct.id = cf.crop_type_id
             LEFT JOIN fields f ON f.id = cf.field_id
             WHERE ce.farm_id = :fid ORDER BY ce.start_date DESC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM climate_events WHERE id = :id AND farm_id = :fid LIMIT 1', ['id' => $id, 'fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, string $userId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('climate_events', [
            'id'                     => $id,
            'farm_id'                => $farmId,
            'event_type'             => $data['event_type'],
            'start_date'             => $data['start_date'],
            'end_date'               => $data['end_date'],
            'description'            => $data['description'],
            'estimated_loss_amount'  => $data['estimated_loss_amount'],
            'affected_crop_field_id' => $data['affected_crop_field_id'],
            'created_by_id'          => $userId,
            'created_at'             => $now,
            'updated_at'             => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('climate_events', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('climate_events', ['id' => $id, 'farm_id' => $farmId]);
    }
}
