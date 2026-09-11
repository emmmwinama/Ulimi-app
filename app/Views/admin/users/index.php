<?php
/**
 * @var array<int,array<string,mixed>> $users
 * @var string $q @var array{total:int,active:int} $counts
 */
$this->layout('layouts/admin');
use App\Support\Dates;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Users</h1>
        <p class="lede"><?= e((string) $counts['active']) ?> active of <?= e((string) $counts['total']) ?> total</p>
    </div>
</div>

<form method="get" action="<?= e(url('admin/users')) ?>" class="mb-16">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email…" style="max-width:320px">
</form>

<div class="table-wrap"><table class="data">
    <thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Farms</th><th>Status</th><th>Joined</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><a href="<?= e(url('admin/users/' . rawurlencode((string) $u['id']))) ?>"><?= e((string) ($u['name'] ?: '—')) ?></a></td>
            <td class="small"><?= e((string) $u['email']) ?></td>
            <td class="small"><?= e((string) ($u['tier_name'] ?: '—')) ?></td>
            <td class="num"><?= e((string) $u['farm_count']) ?></td>
            <td><span class="badge <?= (int) $u['is_active'] === 1 ? 'green' : 'red' ?>"><?= (int) $u['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
            <td class="small muted"><?= e(Dates::forDisplay((string) $u['created_at'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->stop(); ?>
