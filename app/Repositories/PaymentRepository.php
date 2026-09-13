<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class PaymentRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forSubscription(string $subscriptionId): array
    {
        return $this->db->select(
            'SELECT * FROM payments WHERE subscription_id = :sid ORDER BY created_at DESC',
            ['sid' => $subscriptionId],
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function recent(int $limit = 50): array
    {
        return $this->db->select(
            'SELECT p.*, u.name AS user_name, u.email AS user_email
             FROM payments p
             JOIN subscriptions s ON s.id = p.subscription_id
             JOIN users u ON u.id = s.user_id
             ORDER BY p.created_at DESC LIMIT ' . max(1, $limit),
        );
    }

    /** @param array{subscription_id:string,amount:float,currency:string,status:string,method:string,reference:?string,notes:?string,paid_at:?string} $data */
    public function create(array $data, string $adminId): string
    {
        $id = Ulid::generate();
        $this->db->insert('payments', [
            'id' => $id, 'subscription_id' => $data['subscription_id'],
            'amount' => $data['amount'], 'currency' => $data['currency'],
            'status' => $data['status'], 'method' => $data['method'],
            'reference' => $data['reference'], 'notes' => $data['notes'],
            'paid_at' => $data['paid_at'], 'created_at' => Dates::nowUtc(),
            'created_by_admin_id' => $adminId,
        ]);
        return $id;
    }

    public function totalCollected(): float
    {
        return (float) $this->db->scalar("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid'");
    }

    public function totalCollectedThisMonth(): float
    {
        return (float) $this->db->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM payments
             WHERE status = 'paid' AND paid_at >= DATE_FORMAT(UTC_TIMESTAMP(), '%Y-%m-01')",
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function allWithUser(int $limit = 300): array
    {
        return $this->db->select(
            'SELECT p.*, u.name AS user_name, u.email AS user_email, t.name AS tier_name
             FROM payments p
             JOIN subscriptions s ON s.id = p.subscription_id
             JOIN users u ON u.id = s.user_id
             JOIN subscription_tiers t ON t.id = s.tier_id
             ORDER BY p.created_at DESC LIMIT ' . max(1, $limit),
        );
    }
}
