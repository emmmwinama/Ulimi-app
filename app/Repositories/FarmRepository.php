<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class FarmRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM farms WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /**
     * Farms the user can act on, owner farms first, then by name.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forUser(string $userId): array
    {
        return $this->db->select(
            "SELECT f.*, m.role AS member_role
             FROM farm_members m
             JOIN farms f ON f.id = m.farm_id
             WHERE m.user_id = :uid AND m.status = 'active'
             ORDER BY (m.role = 'owner') DESC, f.name ASC",
            ['uid' => $userId],
        );
    }

    public function countForOwner(string $userId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM farms WHERE user_id = :uid',
            ['uid' => $userId],
        );
    }

    /**
     * @param array{name:string,location:string,user_id:string,location_lat?:?float,location_lng?:?float,owner_name?:?string} $data
     */
    public function create(array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('farms', [
            'id'           => $id,
            'name'         => $data['name'],
            'location'     => $data['location'],
            'location_lat' => $data['location_lat'] ?? null,
            'location_lng' => $data['location_lng'] ?? null,
            'owner_name'   => $data['owner_name'] ?? null,
            'user_id'      => $data['user_id'],
            'created_at'   => Dates::nowUtc(),
            'updated_at'   => Dates::nowUtc(),
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $fields */
    public function update(string $id, array $fields): void
    {
        $fields['updated_at'] = Dates::nowUtc();
        $this->db->update('farms', $fields, ['id' => $id]);
    }
}
