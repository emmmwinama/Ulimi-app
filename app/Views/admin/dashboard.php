<?php
/**
 * @var array<string,int> $stats
 * @var array<int,array<string,mixed>> $recent
 */
$this->layout('layouts/admin');
use App\Support\Dates;
?>
<?php $this->start('content'); ?>
<h1 class="h1 mb-16px">Overview</h1>

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
