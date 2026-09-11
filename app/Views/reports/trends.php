<?php
/**
 * @var list<array{month:string,income:float,cost:float}> $trend
 * @var array{income:float,expense_tx:float,activity_cost:float,overhead:float,total_cost:float,net:float,by_category:array<string,float>} $totals
 */
$this->layout('layouts/app');
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Trends</h1>
        <p class="lede">Trailing 12 months</p>
    </div>
    <a class="btn ghost" href="<?= e(url('finance')) ?>">Finance overview →</a>
</div>

<div class="grid cols-3 mb-24px">
    <div class="stat"><div class="label">Income (all-time)</div><div class="value" style="font-size:1.1rem;color:var(--teal)"><?= e(Money::compact($totals['income'])) ?></div></div>
    <div class="stat"><div class="label">Total cost</div><div class="value" style="font-size:1.1rem"><?= e(Money::compact($totals['total_cost'])) ?></div></div>
    <div class="stat"><div class="label">Net</div><div class="value" style="font-size:1.1rem;color:<?= $totals['net'] >= 0 ? 'var(--teal)' : 'var(--red)' ?>"><?= e(Money::compact($totals['net'])) ?></div></div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Income vs cost by month</h2></div>
    <div class="card-body"><?= $this->partial('partials/barchart', ['series' => $trend]) ?></div>
</div>
<?php $this->stop(); ?>
