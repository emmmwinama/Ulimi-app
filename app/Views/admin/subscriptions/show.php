<?php
/**
 * @var array<string,mixed> $sub
 * @var array<int,array<string,mixed>> $tiers @var array<int,array<string,mixed>> $payments
 * @var list<string> $statuses @var list<string> $methods @var list<string> $pstatuses
 */
$this->layout('layouts/admin');
use App\Support\Dates;
use App\Support\Money;
$id = rawurlencode((string) $sub['id']);
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) ($sub['user_name'] ?: $sub['user_email'])) ?></h1>
        <p class="lede"><?= e((string) $sub['tier_name']) ?> · <span class="badge <?= $sub['status'] === 'active' ? 'green' : 'amber' ?>"><?= e(ucfirst((string) $sub['status'])) ?></span></p>
    </div>
</div>

<div class="grid cols-2 mb-24">
    <div class="card">
        <div class="card-head"><h2 class="h2">Status</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/subscriptions/' . $id . '/status')) ?>" class="row" style="gap:8px">
                <?= csrf_field() ?>
                <select class="select" name="status">
                    <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= $s === $sub['status'] ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $s))) ?></option><?php endforeach; ?>
                </select>
                <button class="btn sm" type="submit">Update</button>
            </form>

            <form method="post" action="<?= e(url('admin/subscriptions/' . $id . '/tier')) ?>" class="row mt-16" style="gap:8px">
                <?= csrf_field() ?>
                <select class="select" name="tier_id">
                    <?php foreach ($tiers as $t): ?><option value="<?= e((string) $t['id']) ?>" <?= $t['id'] === $sub['tier_id'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option><?php endforeach; ?>
                </select>
                <button class="btn sm secondary" type="submit">Change plan</button>
            </form>

            <form method="post" action="<?= e(url('admin/subscriptions/' . $id . '/extend')) ?>" class="row mt-16" style="gap:8px">
                <?= csrf_field() ?>
                <input class="input" type="date" name="end_date" value="<?= e((string) ($sub['end_date'] ?? '')) ?>">
                <button class="btn sm secondary" type="submit">Set billing end date</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Record a payment</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('admin/subscriptions/' . $id . '/payments')) ?>" class="grid cols-2" style="gap:10px">
                <?= csrf_field() ?>
                <div class="field"><label class="small">Amount</label><input class="input" type="number" step="any" name="amount" required></div>
                <div class="field"><label class="small">Currency</label><input class="input" name="currency" value="MWK" maxlength="3" required></div>
                <div class="field"><label class="small">Method</label>
                    <select class="select" name="method"><?php foreach ($methods as $m): ?><option value="<?= e($m) ?>"><?= e(ucfirst(str_replace('_', ' ', $m))) ?></option><?php endforeach; ?></select>
                </div>
                <div class="field"><label class="small">Status</label>
                    <select class="select" name="status"><?php foreach ($pstatuses as $s): ?><option value="<?= e($s) ?>"><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select>
                </div>
                <div class="field" style="grid-column:1/-1"><label class="small">Reference</label><input class="input" name="reference"></div>
                <div style="grid-column:1/-1"><button class="btn sm" type="submit">Record payment</button></div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Payment history</h2></div>
    <div class="table-wrap" style="border:0"><table class="data">
        <thead><tr><th>Date</th><th class="num">Amount</th><th>Method</th><th>Status</th><th>Reference</th></tr></thead>
        <tbody>
        <?php if ($payments === []): ?>
            <tr><td colspan="5" class="muted">No payments recorded.</td></tr>
        <?php else: foreach ($payments as $p): ?>
            <tr>
                <td class="small"><?= e(Dates::forDisplay((string) $p['created_at'])) ?></td>
                <td class="num"><?= e(Money::format((float) $p['amount'], (string) $p['currency'])) ?></td>
                <td class="small"><?= e((string) $p['method']) ?></td>
                <td><span class="badge <?= $p['status'] === 'paid' ? 'green' : 'amber' ?>"><?= e((string) $p['status']) ?></span></td>
                <td class="small muted"><?= e((string) ($p['reference'] ?: '—')) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div>
</div>
<?php $this->stop(); ?>
