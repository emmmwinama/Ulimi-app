<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class SubscriptionRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * The user's subscription joined with its tier's limits + feature flags,
     * flattened into one row (tier columns win on name collisions except `id`,
     * `status`, `created_at`, which stay the subscription's).
     *
     * @return array<string,mixed>|null
     */
    public function forUserWithTier(string $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT
                s.id, s.user_id, s.tier_id, s.status, s.billing_cycle,
                s.start_date, s.end_date, s.trial_ends_at, s.created_at,
                t.name AS tier_name, t.currency, t.price_monthly,
                t.max_fields, t.max_crops, t.max_activities, t.max_transactions,
                t.max_employees, t.max_farms, t.max_team_members,
                t.season_analytics, t.yield_suggestions, t.cost_per_hectare,
                t.payroll_tracking, t.multiple_farms, t.team_accounts,
                t.custom_reports, t.api_access, t.sync_enabled
             FROM subscriptions s
             JOIN subscription_tiers t ON t.id = s.tier_id
             WHERE s.user_id = :uid
             LIMIT 1',
            ['uid' => $userId],
        );
    }

    /** @return array<string,mixed>|null */
    public function defaultTier(): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM subscription_tiers
             WHERE is_active = 1
             ORDER BY sort_order ASC, price_monthly ASC
             LIMIT 1",
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function publicTiers(): array
    {
        return $this->db->select(
            "SELECT * FROM subscription_tiers
             WHERE is_active = 1 AND is_public = 1
             ORDER BY sort_order ASC, price_monthly ASC",
        );
    }

    /**
     * @param array{user_id:string,tier_id:string,status:string,billing_cycle?:string,trial_ends_at?:?string,end_date?:?string} $data
     */
    public function create(array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('subscriptions', [
            'id'            => $id,
            'user_id'       => $data['user_id'],
            'tier_id'       => $data['tier_id'],
            'status'        => $data['status'],
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'start_date'    => $now,
            'end_date'      => $data['end_date'] ?? null,
            'trial_ends_at' => $data['trial_ends_at'] ?? null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        return $id;
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->db->update('subscriptions', [
            'status'     => $status,
            'updated_at' => Dates::nowUtc(),
        ], ['id' => $id]);
    }

    /* ------------------------------------------------------------- admin */

    /** @return array<int,array<string,mixed>> */
    public function allWithUser(string $status = ''): array
    {
        $sql = 'SELECT s.*, u.name AS user_name, u.email AS user_email, t.name AS tier_name, t.price_monthly
                FROM subscriptions s
                JOIN users u ON u.id = s.user_id
                JOIN subscription_tiers t ON t.id = s.tier_id';
        $bind = [];
        if ($status !== '') {
            $sql .= ' WHERE s.status = :status';
            $bind['status'] = $status;
        }
        $sql .= ' ORDER BY s.created_at DESC LIMIT 200';
        return $this->db->select($sql, $bind);
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT s.*, u.name AS user_name, u.email AS user_email, t.name AS tier_name
             FROM subscriptions s
             JOIN users u ON u.id = s.user_id
             JOIN subscription_tiers t ON t.id = s.tier_id
             WHERE s.id = :id LIMIT 1',
            ['id' => $id],
        );
    }

    /** @return array<int,array<string,mixed>> all tiers, active or not, for an admin picker */
    public function allTiers(): array
    {
        return $this->db->select('SELECT * FROM subscription_tiers ORDER BY sort_order ASC, price_monthly ASC');
    }

    public function changeTier(string $id, string $tierId): void
    {
        $this->db->update('subscriptions', ['tier_id' => $tierId, 'updated_at' => Dates::nowUtc()], ['id' => $id]);
    }

    public function extendEndDate(string $id, string $endDate): void
    {
        $this->db->update('subscriptions', ['end_date' => $endDate, 'updated_at' => Dates::nowUtc()], ['id' => $id]);
    }

    public function counts(): array
    {
        $rows = $this->db->select('SELECT status, COUNT(*) AS n FROM subscriptions GROUP BY status');
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['status']] = (int) $r['n'];
        }
        return $out;
    }

    /* --------------------------------------------------------- tier admin */

    /** @return array<string,mixed>|null */
    public function findTier(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM subscription_tiers WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function tierInUse(string $id): bool
    {
        return $this->db->scalar('SELECT 1 FROM subscriptions WHERE tier_id = :id LIMIT 1', ['id' => $id]) !== false;
    }

    /** @param array<string,mixed> $data */
    public function createTier(array $data): string
    {
        $id = Ulid::generate();
        $data['id'] = $id;
        $data['created_at'] = Dates::nowUtc();
        $this->db->insert('subscription_tiers', $data);
        return $id;
    }

    /** @param array<string,mixed> $data */
    public function updateTier(string $id, array $data): void
    {
        $this->db->update('subscription_tiers', $data, ['id' => $id]);
    }

    public function deleteTier(string $id): int
    {
        return $this->db->delete('subscription_tiers', ['id' => $id]);
    }
}
