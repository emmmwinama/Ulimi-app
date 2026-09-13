<?php
/**
 * @var array<string,int|float> $stats
 * @var array<int,array<string,mixed>> $tierBreakdown
 * @var array<int,array<string,mixed>> $recentPayments
 * @var array<int,array<string,mixed>> $recentUsers
 * @var array<int,array<string,mixed>> $recent
 */
$this->layout('layouts/admin');
use App\Support\Dates;
use App\Support\Money;

$accents = ['#0F766E', '#2563EB', '#0284C7', '#7C3AED'];
$paleFor = [
    '#0F766E' => '#ECFDF5',
    '#2563EB' => '#EFF6FF',
    '#0284C7' => '#E0F2FE',
    '#7C3AED' => '#F3E8FF',
];
?>
<?php $this->start('content'); ?>
<h1 class="h1">Platform overview</h1>
<p class="lede mb-16px"><?= e((new DateTimeImmutable('now'))->format('l, j F Y')) ?></p>

<div class="grid cols-4">
    <a class="stat" href="<?= e(url('admin/users')) ?>" style="text-decoration:none">
        <div class="icon-row">
            <span class="icon-box" style="background:<?= $paleFor[$accents[0]] ?>;color:<?= $accents[0] ?>"><?= $this->partial('partials/icon', ['name' => 'users', 'class' => 'ico']) ?></span>
        </div>
        <div class="value"><?= e((string) $stats['users']) ?></div>
        <div class="label">Total users</div>
        <div class="delta"><?= e((string) $stats['active_users']) ?> active</div>
    </a>
    <a class="stat" href="<?= e(url('admin/subscriptions')) ?>" style="text-decoration:none">
        <div class="icon-row">
            <span class="icon-box" style="background:<?= $paleFor[$accents[1]] ?>;color:<?= $accents[1] ?>"><?= $this->partial('partials/icon', ['name' => 'wallet', 'class' => 'ico']) ?></span>
        </div>
        <div class="value"><?= e((string) $stats['subscriptions']) ?></div>
        <div class="label">Active subscriptions</div>
        <div class="delta"><?= e((string) $stats['trialing']) ?> on trial</div>
    </a>
    <a class="stat" href="<?= e(url('admin/payments')) ?>" style="text-decoration:none">
        <div class="icon-row">
            <span class="icon-box" style="background:<?= $paleFor[$accents[2]] ?>;color:<?= $accents[2] ?>"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico']) ?></span>
        </div>
        <div class="value"><?= e(Money::format((float) $stats['total_revenue'])) ?></div>
        <div class="label">Total revenue</div>
        <div class="delta">All-time collected</div>
    </a>
    <a class="stat" href="<?= e(url('admin/tiers')) ?>" style="text-decoration:none">
        <div class="icon-row">
            <span class="icon-box" style="background:<?= $paleFor[$accents[3]] ?>;color:<?= $accents[3] ?>"><?= $this->partial('partials/icon', ['name' => 'boxes', 'class' => 'ico']) ?></span>
        </div>
        <div class="value"><?= e((string) $stats['tier_plans']) ?></div>
        <div class="label">Tier plans</div>
        <div class="delta"><?= e((string) $stats['past_due']) ?> past due / expired</div>
    </a>
</div>

<div class="grid cols-2 mt-24px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Subscriptions by tier</h2></div>
        <div class="card-body">
            <?php if ($tierBreakdown === []): ?>
                <p class="small muted">No subscriptions yet.</p>
            <?php else: ?>
                <div class="stack" style="gap:14px">
                    <?php $max = max(1, (int) $stats['users']); foreach ($tierBreakdown as $i => $t):
                        $pct = min(100, ((int) $t['n'] / $max) * 100);
                        $color = $accents[$i % count($accents)];
                    ?>
                        <div>
                            <div class="spread" style="margin-bottom:6px">
                                <span class="small" style="font-weight:700;color:var(--text)"><?= e((string) $t['tier_name']) ?></span>
                                <span class="small" style="font-weight:800;color:<?= $color ?>"><?= e((string) $t['n']) ?></span>
                            </div>
                            <div style="height:8px;border-radius:999px;overflow:hidden;background:var(--surface-2)">
                                <div style="height:100%;border-radius:999px;background:<?= $color ?>;width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head spread">
            <h2 class="h2">Recent payments</h2>
            <a class="small" style="font-weight:700;color:var(--teal)" href="<?= e(url('admin/payments')) ?>">View all →</a>
        </div>
        <div class="card-body">
            <?php if ($recentPayments === []): ?>
                <p class="small muted">No payments yet.</p>
            <?php else: ?>
                <div class="stack" style="gap:0">
                    <?php foreach ($recentPayments as $p): ?>
                        <div class="spread" style="padding:10px 0;border-bottom:1px solid var(--line)">
                            <div>
                                <p class="small" style="font-weight:700;color:var(--text)"><?= e((string) $p['user_name']) ?></p>
                                <p class="small muted"><?= e(Dates::forDisplay((string) ($p['paid_at'] ?? $p['created_at']), 'j M Y')) ?></p>
                            </div>
                            <div style="text-align:right">
                                <p class="small" style="font-weight:800;color:var(--teal)"><?= e(Money::format((float) $p['amount'], (string) $p['currency'])) ?></p>
                                <span class="badge <?= $p['status'] === 'paid' ? 'green' : 'blue' ?>" style="font-size:.6rem"><?= e((string) $p['status']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mt-24px">
    <div class="card-head spread">
        <h2 class="h2">Recent registrations</h2>
        <a class="small" style="font-weight:700;color:var(--teal)" href="<?= e(url('admin/users')) ?>">View all →</a>
    </div>
    <div class="table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>User</th><th>Email</th><th>Farm</th><th>Joined</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($recentUsers === []): ?>
                <tr><td colspan="5" class="muted">No users yet.</td></tr>
            <?php else: foreach ($recentUsers as $u):
                $initials = strtoupper(substr((string) $u['name'], 0, 1) . substr((string) strstr((string) $u['name'], ' '), 1, 1));
            ?>
                <tr>
                    <td>
                        <div class="row" style="gap:10px">
                            <span style="width:30px;height:30px;border-radius:10px;background:var(--navy);color:#fff;display:grid;place-items:center;font-weight:900;font-size:.7rem;flex:none"><?= e(trim($initials) ?: '?') ?></span>
                            <span style="font-weight:700"><?= e((string) $u['name']) ?></span>
                        </div>
                    </td>
                    <td class="small muted"><?= e((string) $u['email']) ?></td>
                    <td class="small" style="color:var(--teal);font-weight:700"><?= e((string) ($u['farm_name'] ?? '—')) ?></td>
                    <td class="small muted"><?= e(Dates::forDisplay((string) $u['created_at'], 'j M Y')) ?></td>
                    <td><span class="badge <?= (int) $u['is_active'] === 1 ? 'green' : 'blue' ?>"><?= (int) $u['is_active'] === 1 ? 'Active' : 'Pending' ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-24px">
    <div class="card-head"><h2 class="h2">Recent activity</h2></div>
    <div class="table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>When (UTC)</th><th>Actor</th><th>Action</th><th>IP</th></tr></thead>
            <tbody>
            <?php if ($recent === []): ?>
                <tr><td colspan="4" class="muted">No activity recorded yet.</td></tr>
            <?php else: foreach ($recent as $r): ?>
                <tr>
                    <td class="mono small"><?= e(Dates::forDisplay((string) $r['created_at'], 'Y-m-d H:i')) ?></td>
                    <td><span class="badge"><?= e((string) $r['actor_type']) ?></span></td>
                    <td class="mono small"><?= e((string) $r['action']) ?></td>
                    <td class="mono small muted"><?= e((string) ($r['ip'] ?? '')) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->stop(); ?>
