<?php
/**
 * @var array<string,mixed>|null $admin
 * @var array<string,int> $stats
 * @var array<int,array<string,mixed>> $recent
 */
use App\Support\Dates;
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin — AgriVault</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
</head>
<body>
<div class="container" style="padding:24px 0 64px">
    <div class="spread">
        <div class="row">
            <span class="badge blue">ADMIN</span>
            <strong><?= e((string) ($admin['name'] ?? 'Administrator')) ?></strong>
        </div>
        <form method="post" action="<?= e(url('admin/logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn secondary sm">Sign out</button>
        </form>
    </div>

    <h1 class="h1 mt-24 mb-16">Overview</h1>

    <div class="grid cols-3">
        <?php
        $tiles = [
            'Users'          => $stats['users'] ?? 0,
            'Active users'   => $stats['active_users'] ?? 0,
            'Farms'          => $stats['farms'] ?? 0,
            'Subscriptions'  => $stats['subscriptions'] ?? 0,
            'On trial'       => $stats['trialing'] ?? 0,
            'Past due / expired' => $stats['past_due'] ?? 0,
        ];
        foreach ($tiles as $label => $value): ?>
            <div class="stat">
                <div class="label"><?= e($label) ?></div>
                <div class="value"><?= e((string) $value) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mt-24">
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

    <p class="muted small mt-24">
        Full admin tools — users, tiers, subscriptions, payments, CMS, inquiries — arrive in Phase 9.
    </p>
</div>
<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
