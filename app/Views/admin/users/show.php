<?php
/**
 * @var array<string,mixed> $user
 * @var array<int,array<string,mixed>> $farms
 */
$this->layout('layouts/admin');
use App\Support\Dates;
$id = rawurlencode((string) $user['id']);
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) ($user['name'] ?: 'Unnamed')) ?></h1>
        <p class="lede"><?= e((string) $user['email']) ?></p>
    </div>
    <form method="post" action="<?= e(url('admin/users/' . $id . '/toggle-active')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn <?= (int) $user['is_active'] === 1 ? 'danger' : '' ?>">
            <?= (int) $user['is_active'] === 1 ? 'Deactivate account' : 'Activate account' ?>
        </button>
    </form>
</div>

<div class="grid cols-3 mb-24">
    <div class="stat"><div class="label">Status</div><div class="value" style="font-size:1.1rem"><span class="badge <?= (int) $user['is_active'] === 1 ? 'green' : 'red' ?>"><?= (int) $user['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></div></div>
    <div class="stat"><div class="label">Plan</div><div class="value" style="font-size:1.1rem"><?= e((string) ($user['tier_name'] ?: '—')) ?></div></div>
    <div class="stat"><div class="label">Joined</div><div class="value" style="font-size:1.1rem"><?= e(Dates::forDisplay((string) $user['created_at'])) ?></div></div>
</div>

<?php if ($user['subscription_id']): ?>
    <p class="mb-16"><a class="btn secondary sm" href="<?= e(url('admin/subscriptions/' . rawurlencode((string) $user['subscription_id']))) ?>">View subscription →</a></p>
<?php endif; ?>

<div class="card">
    <div class="card-head"><h2 class="h2">Farms</h2></div>
    <div class="table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>Name</th><th>Location</th><th class="num">Team</th><th>Created</th></tr></thead>
            <tbody>
            <?php if ($farms === []): ?>
                <tr><td colspan="4" class="muted">No farms.</td></tr>
            <?php else: foreach ($farms as $f): ?>
                <tr>
                    <td><?= e((string) $f['name']) ?></td>
                    <td class="small"><?= e((string) $f['location']) ?></td>
                    <td class="num"><?= e((string) $f['member_count']) ?></td>
                    <td class="small muted"><?= e(Dates::forDisplay((string) $f['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->stop(); ?>
