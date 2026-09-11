<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Admin-facing view over customer accounts. Read-mostly; the one mutation is
 * the activation toggle (a blunt "lock the account out" control alongside the
 * self-service activation-token flow).
 */
final class AdminUserRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function all(string $search = ''): array
    {
        $sql = "SELECT u.*, s.status AS sub_status, t.name AS tier_name,
                       (SELECT COUNT(*) FROM farms f WHERE f.user_id = u.id) AS farm_count
                FROM users u
                LEFT JOIN subscriptions s ON s.user_id = u.id
                LEFT JOIN subscription_tiers t ON t.id = s.tier_id";
        $bind = [];
        if ($search !== '') {
            $sql .= ' WHERE u.email LIKE :q OR u.name LIKE :q2';
            $bind['q'] = '%' . $search . '%';
            $bind['q2'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY u.created_at DESC LIMIT 200';
        return $this->db->select($sql, $bind);
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT u.*, s.id AS subscription_id, s.status AS sub_status, s.tier_id, t.name AS tier_name
             FROM users u
             LEFT JOIN subscriptions s ON s.user_id = u.id
             LEFT JOIN subscription_tiers t ON t.id = s.tier_id
             WHERE u.id = :id LIMIT 1',
            ['id' => $id],
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function farmsFor(string $userId): array
    {
        return $this->db->select(
            "SELECT f.*, (SELECT COUNT(*) FROM farm_members m WHERE m.farm_id = f.id AND m.status = 'active') AS member_count
             FROM farms f WHERE f.user_id = :uid ORDER BY f.created_at DESC",
            ['uid' => $userId],
        );
    }

    public function setActive(string $id, bool $active): void
    {
        $this->db->update('users', ['is_active' => $active ? 1 : 0], ['id' => $id]);
    }

    public function counts(): array
    {
        return [
            'total'  => (int) $this->db->scalar('SELECT COUNT(*) FROM users'),
            'active' => (int) $this->db->scalar('SELECT COUNT(*) FROM users WHERE is_active = 1'),
        ];
    }
}
