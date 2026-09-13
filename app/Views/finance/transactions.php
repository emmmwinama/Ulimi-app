<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var list<string> $seasons @var array<string,string> $filters
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$income = array_sum(array_map(static fn ($r) => $r['type'] === 'Income' ? (float) $r['amount'] : 0, $rows));
$expense = array_sum(array_map(static fn ($r) => $r['type'] === 'Expense' ? (float) $r['amount'] : 0, $rows));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Transactions</h1>
        <p class="lede"><?= e(Money::format($income)) ?> in · <?= e(Money::format($expense)) ?> out</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="#add-transaction">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add transaction
        </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('finance/transactions')) ?>" class="row wrap mb-24px" style="gap:10px">
    <select class="select" name="type" onchange="this.form.submit()" style="max-width:160px">
        <option value="">All types</option>
        <option value="Income" <?= $filters['type'] === 'Income' ? 'selected' : '' ?>>Income</option>
        <option value="Expense" <?= $filters['type'] === 'Expense' ? 'selected' : '' ?>>Expense</option>
    </select>
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:240px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $filters['season'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if (array_filter($filters)): ?><a class="btn ghost sm" href="<?= e(url('finance/transactions')) ?>">Clear</a><?php endif; ?>
</form>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No transactions</div><p>Record income and expenses; sales made from Inventory appear here automatically.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th>Crop</th><th>Payment</th><th class="num">Amount</th><?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php
        $payBadge = ['paid' => 'green', 'partial' => 'amber', 'unpaid' => 'red'];
        $payLabel = ['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'];
        ?>
        <?php foreach ($rows as $r): $ps = (string) ($r['payment_status'] ?? 'paid'); ?>
            <tr>
                <td class="small nowrap"><?= e(Dates::forDisplay((string) $r['date'])) ?></td>
                <td><span class="badge <?= $r['type'] === 'Income' ? 'green' : 'amber' ?>"><?= e((string) $r['type']) ?></span></td>
                <td class="small"><?= e((string) $r['category']) ?></td>
                <td><?= e((string) $r['description']) ?><?php if (($r['source'] ?? '') === 'inventory_sale'): ?> <span class="badge">auto</span><?php endif; ?></td>
                <td class="muted small"><?= e((string) ($r['crop_name'] ?? '—')) ?></td>
                <td><span class="badge <?= $payBadge[$ps] ?? '' ?>"><?= e($payLabel[$ps] ?? ucfirst($ps)) ?></span></td>
                <td class="num"><?= e(Money::format((float) $r['amount'])) ?></td>
                <?php if ($canManage): ?>
                    <td class="num nowrap">
                        <?php if (($r['source'] ?? 'manual') === 'manual'): ?>
                            <a class="btn sm ghost" href="#edit-transaction-<?= e((string) $r['id']) ?>">Edit</a>
                        <?php endif; ?>
                        <form method="post" action="<?= e(url('finance/transactions/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                              style="display:inline" onsubmit="return confirm('Delete this transaction?')">
                            <?= csrf_field() ?>
                            <button class="btn sm ghost danger" type="submit">Delete</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>

<?php if ($canManage): ?>
    <div class="slide-over" id="add-transaction">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Add transaction</h2>
                    <p class="small muted mt-8px">Record income or an expense</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('finance/transactions')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/finance/transaction', [
                        'row' => null, 'fields' => $txFields, 'crops' => $txCrops, 'incomeCats' => $incomeCats, 'expenseCats' => $expenseCats,
                    ]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add transaction</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($rows as $r): if (($r['source'] ?? 'manual') !== 'manual') continue; ?>
        <div class="slide-over" id="edit-transaction-<?= e((string) $r['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit transaction</h2>
                        <p class="small muted mt-8px"><?= e((string) $r['description']) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('finance/transactions/' . rawurlencode((string) $r['id']))) ?>" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/finance/transaction', [
                            'row' => $r, 'fields' => $txFields, 'crops' => $txCrops, 'incomeCats' => $incomeCats, 'expenseCats' => $expenseCats,
                        ]) ?>
                    </div>
                    <div class="panel-foot">
                        <a href="#" class="btn ghost block">Cancel</a>
                        <button type="submit" class="btn block">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php $this->stop(); ?>
