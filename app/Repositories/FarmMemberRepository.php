<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class FarmMemberRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<string,mixed>|null the active membership row, or null */
    public function membership(string $userId, string $farmId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM farm_members
             WHERE user_id = :uid AND farm_id = :fid AND status = 'active'
             LIMIT 1",
            ['uid' => $userId, 'fid' => $farmId],
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId): array
    {
        return $this->db->select(
            "SELECT m.*, u.name AS user_name, u.email AS user_email
             FROM farm_members m
             JOIN users u ON u.id = m.user_id
             WHERE m.farm_id = :fid
             ORDER BY (m.role = 'owner') DESC, u.name ASC",
            ['fid' => $farmId],
        );
    }

    public function countActive(string $farmId): int
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*) FROM farm_members WHERE farm_id = :fid AND status = 'active'",
            ['fid' => $farmId],
        );
    }

    /**
     * @param array{farm_id:string,user_id:string,role:string,permissions?:array<string,mixed>,status?:string,invited_by?:?string} $data
     */
    public function create(array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('farm_members', [
            'id'          => $id,
            'farm_id'     => $data['farm_id'],
            'user_id'     => $data['user_id'],
            'role'        => $data['role'],
            'permissions' => json_encode($data['permissions'] ?? new \stdClass(), JSON_UNESCAPED_SLASHES),
            'invite_email'=> null,
            'invite_token'=> null,
            'invite_expires_at' => null,
            'status'      => $data['status'] ?? 'active',
            'invited_by'  => $data['invited_by'] ?? null,
            'created_at'  => Dates::nowUtc(),
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $fields */
    public function update(string $id, array $fields): void
    {
        $this->db->update('farm_members', $fields, ['id' => $id]);
    }
}
