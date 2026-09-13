<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\PaymentRepository;

final class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        $db = Database::instance();

        $stats = [
            'users'         => (int) $db->scalar('SELECT COUNT(*) FROM users'),
            'active_users'  => (int) $db->scalar('SELECT COUNT(*) FROM users WHERE is_active = 1'),
            'farms'         => (int) $db->scalar('SELECT COUNT(*) FROM farms'),
            'subscriptions' => (int) $db->scalar("SELECT COUNT(*) FROM subscriptions WHERE status IN ('trial','active')"),
            'trialing'      => (int) $db->scalar("SELECT COUNT(*) FROM subscriptions WHERE status = 'trial'"),
            'past_due'      => (int) $db->scalar("SELECT COUNT(*) FROM subscriptions WHERE status IN ('past_due','expired')"),
            'tier_plans'    => (int) $db->scalar('SELECT COUNT(*) FROM subscription_tiers WHERE is_active = 1'),
            'total_revenue' => $this->payments->totalCollected(),
        ];

        $tierBreakdown = $db->select(
            "SELECT t.name AS tier_name, COUNT(*) AS n
             FROM subscriptions s
             JOIN subscription_tiers t ON t.id = s.tier_id
             GROUP BY t.id, t.name
             ORDER BY n DESC",
        );

        $recentPayments = $this->payments->recent(6);

        $recentUsers = $db->select(
            "SELECT u.id, u.name, u.email, u.is_active, u.created_at,
                    (SELECT f.name FROM farms f WHERE f.user_id = u.id ORDER BY f.created_at ASC LIMIT 1) AS farm_name
             FROM users u
             ORDER BY u.created_at DESC
             LIMIT 8",
        );

        $recent = $db->select(
            'SELECT action, actor_type, ip, created_at
             FROM audit_log
             ORDER BY created_at DESC
             LIMIT 15',
        );

        return $this->view('admin/dashboard', [
            'title'          => 'Overview',
            'active'         => 'dashboard',
            'admin'          => AdminAuth::admin(),
            'stats'          => $stats,
            'tierBreakdown'  => $tierBreakdown,
            'recentPayments' => $recentPayments,
            'recentUsers'    => $recentUsers,
            'recent'         => $recent,
        ]);
    }
}
