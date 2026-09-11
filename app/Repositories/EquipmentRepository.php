<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class EquipmentRepository
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
            'SELECT e.*,
                    (SELECT COUNT(*) FROM equipment_maintenance_logs l WHERE l.equipment_id = e.id) AS log_count,
                    (SELECT COALESCE(SUM(l.cost), 0) FROM equipment_maintenance_logs l WHERE l.equipment_id = e.id) AS maintenance_cost
             FROM equipment e WHERE e.farm_id = :fid ORDER BY e.status ASC, e.name ASC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM equipment WHERE id = :id AND farm_id = :fid LIMIT 1', ['id' => $id, 'fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('equipment', [
            'id'               => $id,
            'farm_id'          => $farmId,
            'name'             => $data['name'],
            'category'         => $data['category'],
            'status'           => $data['status'],
            'acquisition_date' => $data['acquisition_date'],
            'acquisition_cost' => $data['acquisition_cost'],
            'notes'            => $data['notes'],
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('equipment', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('equipment', ['id' => $id, 'farm_id' => $farmId]);
    }

    /* ---------------------------------------------------------- maintenance */

    /** @return array<int,array<string,mixed>> */
    public function logsFor(string $farmId, string $equipmentId): array
    {
        return $this->db->select(
            'SELECT * FROM equipment_maintenance_logs WHERE farm_id = :fid AND equipment_id = :eid ORDER BY date DESC, created_at DESC',
            ['fid' => $farmId, 'eid' => $equipmentId],
        );
    }

    /** @param array<string,mixed> $data */
    public function addLog(string $farmId, string $equipmentId, array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('equipment_maintenance_logs', [
            'id'           => $id,
            'farm_id'      => $farmId,
            'equipment_id' => $equipmentId,
            'date'         => $data['date'],
            'description'  => $data['description'],
            'cost'         => $data['cost'],
            'hours_used'   => $data['hours_used'],
            'notes'        => $data['notes'],
            'created_at'   => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function deleteLog(string $farmId, string $logId): int
    {
        return $this->db->delete('equipment_maintenance_logs', ['id' => $logId, 'farm_id' => $farmId]);
    }
}
