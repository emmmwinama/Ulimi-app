<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class BuyerRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId): array
    {
        return $this->db->select('SELECT * FROM buyers WHERE farm_id = :fid ORDER BY name ASC', ['fid' => $farmId]);
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM buyers WHERE id = :id AND farm_id = :fid LIMIT 1', ['id' => $id, 'fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('buyers', [
            'id'         => $id,
            'farm_id'    => $farmId,
            'name'       => $data['name'],
            'type'       => $data['type'],
            'phone'      => $data['phone'],
            'email'      => $data['email'],
            'location'   => $data['location'],
            'notes'      => $data['notes'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('buyers', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('buyers', ['id' => $id, 'farm_id' => $farmId]);
    }
}
