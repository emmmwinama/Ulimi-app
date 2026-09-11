<?php
/**
 * @var string $season @var list<string> $seasons
 * @var array{income:float,expense_tx:float,activity_cost:float,overhead:float,total_cost:float,net:float,by_category:array<string,float>} $totals
 * @var list<array{month:string,income:float,cost:float}> $trend
 * @var ?float $costPerHa @var ?float $costPerKg @var float $yieldKg
 * @var bool $showPerHa @var bool $showAnalytics
 */
$this->layout('layouts/app');
use App\Support\Money;
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Finance</h1>
        <p class="lede">Income, cost and margin<?= $season ? ' — ' . e($season) : ' — all seasons' ?></p>
    </div>
    <div class="row">
        <a class="btn secondary" href="<?= e(url('finance/overheads')) ?>">Overheads</a>
        <a class="btn" href="<?= e(url('finance/transactions')) ?>">Transactions</a>
    </div>
</div>

<form method="get" action="<?= e(url('finance')) ?>" class="mb-16px">
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:260px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div class="grid cols-4">
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--teal-pale)"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem;color:var(--teal)"><?= e(Money::compact($totals['income'])) ?></div>
        <div class="label">Income</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--red-050);color:var(--red-text)"><?= $this->partial('partials/icon', ['name' => 'trend-down', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem"><?= e(Money::compact($totals['total_cost'])) ?></div>
        <div class="label">Total cost</div>
    </div>
    <div class="stat">
        <div class="icon-row">
            <span class="icon-box" style="background:<?= $totals['net'] >= 0 ? 'var(--teal-pale)' : 'var(--red-050)' ?>;color:<?= $totals['net'] >= 0 ? 'var(--teal)' : 'var(--red-text)' ?>">
                <?= $this->partial('partials/icon', ['name' => $totals['net'] >= 0 ? 'trend-up' : 'trend-down', 'class' => 'ico']) ?>
            </span>
        </div>
        <div class="value" style="font-size:1.4rem;color:<?= $totals['net'] >= 0 ? 'var(--teal)' : 'var(--red)' ?>"><?= e(Money::compact($totals['net'])) ?></div>
        <div class="label">Net margin</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--blue-050);color:var(--blue)"><?= $this->partial('partials/icon', ['name' => 'wheat', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem"><?= e(number_format($yieldKg, 0)) ?> kg</div>
        <div class="label">Yield</div>
    </div>
</div>

<?php if ($showPerHa): ?>
<div class="grid cols-2 mt-16px">
    <div class="stat"><div class="label">Cost per hectare</div><div class="value" style="font-size:1.15rem"><?= $costPerHa !== null ? e(Money::format($costPerHa)) : '—' ?></div></div>
    <div class="stat"><div class="label">Cost per kg</div><div class="value" style="font-size:1.15rem"><?= $costPerKg !== null ? e(Money::format($costPerKg)) : '—' ?></div></div>
</div>
<?php endif; ?>

<div class="card mt-24px">
    <div class="card-head"><h2 class="h2">Income vs cost</h2></div>
    <div class="card-body">
        <?= $this->partial('partials/barchart', ['series' => $trend]) ?>
    </div>
</div>

<div class="grid cols-2 mt-24px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Cost breakdown</h2></div>
        <div class="card-body">
            <table class="data">
                <tbody>
                    <tr><td>Expense transactions</td><td class="num"><?= e(Money::format($totals['expense_tx'])) ?></td></tr>
                    <tr><td>Activity costs (labour, inputs, other)</td><td class="num"><?= e(Money::format($totals['activity_cost'])) ?></td></tr>
                    <tr><td>Overheads<?= $season ? ' (all seasons only)' : '' ?></td><td class="num"><?= e(Money::format($totals['overhead'])) ?></td></tr>
                    <tr style="font-weight:800"><td>Total</td><td class="num"><?= e(Money::format($totals['total_cost'])) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="h2">Expense categories</h2></div>
        <div class="card-body">
            <?php if ($totals['by_category'] === []): ?>
                <p class="muted">No expense transactions yet.</p>
            <?php else: ?>
                <table class="data"><tbody>
                <?php foreach ($totals['by_category'] as $cat => $amt): ?>
                    <tr><td><?= e($cat) ?></td><td class="num"><?= e(Money::format($amt)) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
