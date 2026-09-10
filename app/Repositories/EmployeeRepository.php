<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class EmployeeRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId, bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM employees WHERE farm_id = :fid';
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY is_active DESC, name ASC';
        return $this->db->select($sql, ['fid' => $farmId]);
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM employees WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countActive(string $farmId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM employees WHERE farm_id = :fid AND is_active = 1',
            ['fid' => $farmId],
        );
    }

    /**
     * @param array{name:string,role:string,pay_rate:float,pay_rate_unit:string,phone:?string,is_active:int} $data
     */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('employees', [
            'id'            => $id,
            'farm_id'       => $farmId,
            'name'          => $data['name'],
            'role'          => $data['role'],
            'pay_rate'      => $data['pay_rate'],
            'pay_rate_unit' => $data['pay_rate_unit'],
            'phone'         => $data['phone'],
            'is_active'     => $data['is_active'],
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('employees', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('employees', ['id' => $id, 'farm_id' => $farmId]);
    }

    public function hasLabourRecords(string $farmId, string $id): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM activity_labour WHERE employee_id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        ) !== false;
    }
}
