<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var string $status @var list<string> $statuses @var array<string,int> $counts
 */
$this->layout('layouts/admin');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1">Subscriptions</h1></div></div>

<div class="grid cols-4 mb-16px">
    <?php foreach (['trial', 'active', 'past_due', 'expired'] as $s): ?>
        <div class="stat"><div class="label"><?= e(ucfirst(str_replace('_', ' ', $s))) ?></div><div class="value"><?= e((string) ($counts[$s] ?? 0)) ?></div></div>
    <?php endforeach; ?>
</div>

<form method="get" action="<?= e(url('admin/subscriptions')) ?>" class="mb-24px">
    <select class="select" name="status" onchange="this.form.submit()" style="max-width:200px">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option><?php endforeach; ?>
    </select>
</form>

<div class="table-wrap"><table class="data">
    <thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Cycle</th><th>Ends</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $s): ?>
        <tr>
            <td><a href="<?= e(url('admin/subscriptions/' . rawurlencode((string) $s['id']))) ?>"><?= e((string) ($s['user_name'] ?: $s['user_email'])) ?></a></td>
            <td class="small"><?= e((string) $s['tier_name']) ?> <span class="muted"><?= e(Money::format((float) $s['price_monthly'])) ?>/mo</span></td>
            <td><span class="badge <?= $s['status'] === 'active' ? 'green' : ($s['status'] === 'trial' ? 'blue' : 'red') ?>"><?= e(ucfirst(str_replace('_', ' ', (string) $s['status']))) ?></span></td>
            <td class="small"><?= e((string) $s['billing_cycle']) ?></td>
            <td class="small muted"><?= $s['end_date'] ? e(Dates::forDisplay((string) $s['end_date'])) : '—' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->stop(); ?>
