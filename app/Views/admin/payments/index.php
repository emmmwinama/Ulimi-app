<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $users
 * @var list<string> $methods
 * @var float $total
 * @var float $thisMonth
 */
$this->layout('layouts/admin');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Payments</h1>
        <p class="lede"><?= count($rows) ?> recorded</p>
    </div>
    <a href="#record-payment" class="btn"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Record payment</a>
</div>

<div class="grid cols-3 mb-16px">
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:#ECFDF5;color:#0F766E"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico']) ?></span></div>
        <div class="value"><?= e(Money::format($total)) ?></div>
        <div class="label">Total revenue</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:#EFF6FF;color:#2563EB"><?= $this->partial('partials/icon', ['name' => 'receipt', 'class' => 'ico']) ?></span></div>
        <div class="value"><?= count($rows) ?></div>
        <div class="label">Payments</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:#E0F2FE;color:#0284C7"><?= $this->partial('partials/icon', ['name' => 'bar-chart', 'class' => 'ico']) ?></span></div>
        <div class="value"><?= e(Money::format($thisMonth)) ?></div>
        <div class="label">This month</div>
    </div>
</div>

<div class="table-wrap"><table class="data">
    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>
    <?php if ($rows === []): ?>
        <tr><td colspan="6" class="muted">No payments recorded yet.</td></tr>
    <?php else: foreach ($rows as $p): ?>
        <tr>
            <td>
                <div style="font-weight:700"><?= e((string) $p['user_name']) ?></div>
                <div class="small muted"><?= e((string) $p['tier_name']) ?></div>
            </td>
            <td style="font-weight:800;color:var(--teal)"><?= e(Money::format((float) $p['amount'], (string) $p['currency'])) ?></td>
            <td class="small muted"><?= e(ucfirst(str_replace('_', ' ', (string) $p['method']))) ?></td>
            <td class="mono small muted"><?= e((string) ($p['reference'] ?: '—')) ?></td>
            <td class="small muted"><?= $p['paid_at'] ? e(Dates::forDisplay((string) $p['paid_at'], 'j M Y')) : '—' ?></td>
            <td><span class="badge <?= $p['status'] === 'paid' ? 'green' : ($p['status'] === 'failed' ? 'red' : 'blue') ?>"><?= e(ucfirst((string) $p['status'])) ?></span></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table></div>

<div class="slide-over" id="record-payment">
    <a href="#" class="scrim" aria-label="Close"></a>
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2 class="h3">Record payment</h2>
                <p class="small muted mt-8px">Log a manual payment against a user's subscription</p>
            </div>
            <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
        </div>
        <form method="post" action="<?= e(url('admin/payments')) ?>" style="display:contents">
            <?= csrf_field() ?>
            <div class="panel-body stack">
                <div class="field">
                    <label class="small">User</label>
                    <select class="select" name="user_id" required>
                        <option value="">Select user…</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= e((string) $u['id']) ?>"><?= e((string) $u['name']) ?> (<?= e((string) $u['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($er = error_for('user_id')): ?><p class="err mt-8px"><?= e($er) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="small">Amount (MWK)</label>
                    <input class="input" type="number" step="0.01" min="0" name="amount" required>
                    <?php if ($er = error_for('amount')): ?><p class="err mt-8px"><?= e($er) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label class="small">Payment method</label>
                    <select class="select" name="method">
                        <?php foreach ($methods as $m): ?><option value="<?= e($m) ?>"><?= e(ucfirst(str_replace('_', ' ', $m))) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="small">Reference / receipt #</label>
                    <input class="input" name="reference">
                </div>
                <div class="field">
                    <label class="small">Notes</label>
                    <input class="input" name="notes">
                </div>
            </div>
            <div class="panel-foot">
                <a href="#" class="btn ghost block">Cancel</a>
                <button type="submit" class="btn block">Record</button>
            </div>
        </form>
    </div>
</div>
<?php $this->stop(); ?>
