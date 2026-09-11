<?php
/**
 * Server-rendered grouped bar chart (income vs expenses, net labelled above
 * each group) — no JS, prints cleanly.
 *
 * @var list<array{month:string,income:float,expenses:float,net:float}> $series
 * @var bool $fullLabel  when true, print the month/season label as-is instead of slicing to "MM"
 */
use App\Support\Money;
$series = $series ?? [];
$fullLabel = $fullLabel ?? false;
$max = 0.0;
foreach ($series as $s) {
    $max = max($max, $s['income'], $s['expenses']);
}
$max = $max > 0 ? $max : 1;

$w = 760;
$h = 260;
$pad = 30;
$padTop = 34;
$plotH = $h - $pad - $padTop;
$n = max(1, count($series));
$slot = ($w - $pad * 2) / $n;
$barW = min(20, $slot / 3);
?>
<?php if ($series === []): ?>
    <div class="empty" style="padding:32px"><p class="small muted">No monthly cashflow yet</p></div>
<?php else: ?>
<div class="table-wrap" style="border:0;overflow-x:auto">
<svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="Cashflow by month" style="min-width:640px;width:100%;height:auto">
    <line x1="<?= $pad ?>" y1="<?= $h - $pad ?>" x2="<?= $w - $pad ?>" y2="<?= $h - $pad ?>" stroke="var(--line-strong)" />
    <?php foreach ($series as $i => $s):
        $x = $pad + $slot * $i + $slot / 2;
        $incH = ($s['income'] / $max) * $plotH;
        $expH = ($s['expenses'] / $max) * $plotH;
        $baseY = $h - $pad;
        $netColor = $s['net'] >= 0 ? '#2563EB' : '#DC2626';
    ?>
        <text x="<?= round($x, 1) ?>" y="<?= $padTop - 16 ?>" text-anchor="middle" font-size="9" font-weight="800" fill="<?= $netColor ?>">
            <?= $s['net'] < 0 ? '-' : '' ?><?= e(Money::compact(abs($s['net']))) ?>
        </text>
        <rect x="<?= round($x - $barW - 1, 1) ?>" y="<?= round($baseY - $incH, 1) ?>" width="<?= $barW ?>" height="<?= round($incH, 1) ?>" fill="#16A34A" rx="2">
            <title><?= e($s['month']) ?> income: <?= e(Money::format($s['income'])) ?></title>
        </rect>
        <rect x="<?= round($x + 1, 1) ?>" y="<?= round($baseY - $expH, 1) ?>" width="<?= $barW ?>" height="<?= round($expH, 1) ?>" fill="#DC2626" rx="2">
            <title><?= e($s['month']) ?> expenses: <?= e(Money::format($s['expenses'])) ?></title>
        </rect>
        <text x="<?= round($x, 1) ?>" y="<?= $h - $pad + 14 ?>" text-anchor="middle" font-size="9" fill="var(--text-faint)">
            <?= e($fullLabel ? $s['month'] : substr($s['month'], 5)) ?>
        </text>
    <?php endforeach; ?>
</svg>
</div>
<p class="small muted">
    <span style="display:inline-block;width:10px;height:10px;background:#16A34A;border-radius:2px"></span> Income
    &nbsp;·&nbsp;
    <span style="display:inline-block;width:10px;height:10px;background:#DC2626;border-radius:2px"></span> Expenses
    &nbsp;·&nbsp; figure above each pair is net profit/loss for that month
</p>
<?php endif; ?>
