<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Money;
$a = $dashboard['analytics'];

/**
 * @param list<array<string,mixed>> $rows
 */
$renderList = function (string $title, array $rows, callable $render, string $empty): void {
    ?>
    <div class="card"><div class="card-body">
        <p class="eyebrow mb-12px"><?= e($title) ?></p>
        <?php if ($rows === []): ?>
            <p class="small muted"><?= e($empty) ?></p>
        <?php else: ?>
            <div class="stack" style="--stack-gap:8px">
                <?php foreach (array_slice($rows, 0, 6) as $row):
                    $item = $render($row);
                ?>
                    <div class="spread" style="background:var(--surface-2);border:1px solid var(--line);border-radius:14px;padding:10px 14px">
                        <div style="min-width:0">
                            <p class="small nowrap" style="font-weight:800"><?= e($item['label']) ?></p>
                            <p style="font-size:.7rem;color:var(--text-faint)" class="nowrap"><?= e($item['meta']) ?></p>
                        </div>
                        <p class="small nowrap" style="font-weight:900;color:<?= $item['positive'] ? 'var(--teal)' : 'var(--red-text)' ?>"><?= e($item['value']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>
    <?php
};
?>
<div class="card mb-16px">
    <div class="card-head"><h2 class="h2">Cashflow by month</h2></div>
    <div class="card-body">
        <?= $this->partial('partials/charts/cashflowbar', ['series' => $dashboard['finance']['cashflow_by_month']]) ?>
    </div>
</div>

<div class="grid cols-2" style="gap:16px">
    <?php
    $renderList('Crop profitability ranking', $a['crop_profitability'], static fn ($row) => [
        'label'    => $row['crop_name'] . ($row['variety'] !== '' ? ' — ' . $row['variety'] : ''),
        'meta'     => $row['field_name'] . ' · ' . $row['season'],
        'value'    => Money::format($row['net_profit']),
        'positive' => $row['net_profit'] >= 0,
    ], 'No crop profitability data yet');

    $renderList('Field profitability comparison', $a['field_profitability'], static fn ($row) => [
        'label'    => $row['field_name'],
        'meta'     => number_format($row['area'], 2) . ' ha · ' . Money::format($row['profit_per_ha']) . '/ha',
        'value'    => Money::format($row['net_profit']),
        'positive' => $row['net_profit'] >= 0,
    ], 'No field profitability data yet');

    $renderList('Input efficiency report', $a['input_efficiency'], static fn ($row) => [
        'label'    => $row['crop_name'] . ' — ' . $row['field_name'],
        'meta'     => Money::format($row['cost_per_ha']) . '/ha · ' . Money::format($row['cost_per_kg']) . '/kg',
        'value'    => number_format($row['yield_response'], 2) . ' kg/' . config('app.currency'),
        'positive' => $row['yield_response'] > 0,
    ], 'No input efficiency data yet');

    $renderList('Livestock profitability & health cost', $a['livestock_profitability'], static fn ($row) => [
        'label'    => $row['type'],
        'meta'     => $row['count'] . ' animals · health ' . Money::format($row['health_cost']),
        'value'    => Money::format($row['net_profit']),
        'positive' => $row['net_profit'] >= 0,
    ], 'No livestock analytics yet');
    ?>
</div>
