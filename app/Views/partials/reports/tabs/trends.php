<?php
/** @var array<string,mixed> $dashboard */
$t = $dashboard['trends'];
$cropTypes = $dashboard['all_crop_types'];

$revenueVsExpensesAsCashflow = array_map(static fn ($r) => [
    'month'    => $r['season'],
    'income'   => $r['revenue'],
    'expenses' => $r['expenses'],
    'net'      => $r['revenue'] - $r['expenses'],
], $t['revenue_vs_expenses']);
?>
<div class="stack" style="--stack-gap:16px">
    <div class="card">
        <div class="card-body">
            <p style="font-weight:800;color:var(--text)">Yield per hectare by season</p>
            <p class="small muted mb-12px">kg harvested per hectare planted — tracks if you're getting more from the same land</p>
            <?= $this->partial('partials/charts/linechart', ['series' => $t['yield_trend'], 'cropTypes' => $cropTypes, 'valueSuffix' => ' kg/ha']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p style="font-weight:800;color:var(--text)">Cost of production per hectare</p>
            <p class="small muted mb-12px">Total <?= e((string) config('app.currency')) ?> spent per hectare — rising costs = tighter margins</p>
            <?= $this->partial('partials/charts/linechart', ['series' => $t['cost_per_ha_trend'], 'cropTypes' => $cropTypes, 'valueSuffix' => '/ha']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p style="font-weight:800;color:var(--text)">Cost per kg produced</p>
            <p class="small muted mb-12px">How much it costs to produce 1 kg — key efficiency metric</p>
            <?= $this->partial('partials/charts/linechart', ['series' => $t['cost_per_kg_trend'], 'cropTypes' => $cropTypes, 'valueSuffix' => '/kg']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p style="font-weight:800;color:var(--text)">Revenue vs expenses by season</p>
            <p class="small muted mb-12px">Income against total production cost — the core P&amp;L view</p>
            <?= $this->partial('partials/charts/cashflowbar', ['series' => $revenueVsExpensesAsCashflow, 'fullLabel' => true]) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p style="font-weight:800;color:var(--text)">Expense breakdown by season</p>
            <p class="small muted mb-12px">Where your money goes each season — inputs, labour, overhead</p>
            <?= $this->partial('partials/charts/stackedbar', ['series' => $t['revenue_vs_expenses']]) ?>
        </div>
    </div>
</div>
