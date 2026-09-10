<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Phase 1 admin landing: headline counts only. The full back office (users,
 * tiers, subscriptions, payments, CMS, inquiries) is Phase 9.
 */
final class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $db = Database::instance();

        $stats = [
            'users'         => (int) $db->scalar('SELECT COUNT(*) FROM users'),
            'active_users'  => (int) $db->scalar('SELECT COUNT(*) FROM users WHERE is_active = 1'),
            'farms'         => (int) $db->scalar('SELECT COUNT(*) FROM farms'),
            'subscriptions' => (int) $db->scalar('SELECT COUNT(*) FROM subscriptions'),
            'trialing'      => (int) $db->scalar("SELECT COUNT(*) FROM subscriptions WHERE status = 'trial'"),
            'past_due'      => (int) $db->scalar("SELECT COUNT(*) FROM subscriptions WHERE status IN ('past_due','expired')"),
        ];

        $recent = $db->select(
            'SELECT action, actor_type, ip, created_at
             FROM audit_log
             ORDER BY created_at DESC
             LIMIT 15',
        );

        return $this->view('admin/dashboard', [
            'title'  => 'Admin',
            'admin'  => AdminAuth::admin(),
            'stats'  => $stats,
            'recent' => $recent,
        ]);
    }
}
