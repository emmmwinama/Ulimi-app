<?php
/**
 * Server-rendered grouped bar chart (income vs cost) — no JS, prints cleanly.
 *
 * @var list<array{month:string,income:float,cost:float}> $series
 */
$series = $series ?? [];
$max = 0.0;
foreach ($series as $s) {
    $max = max($max, $s['income'], $s['cost']);
}
$max = $max > 0 ? $max : 1;

$w = 720;
$h = 220;
$pad = 28;
$plotH = $h - $pad * 2;
$n = max(1, count($series));
$slot = ($w - $pad * 2) / $n;
$barW = min(16, $slot / 3);
?>
<div class="table-wrap" style="border:0;overflow-x:auto">
<svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" aria-label="Monthly income versus cost" style="min-width:640px;width:100%;height:auto">
    <line x1="<?= $pad ?>" y1="<?= $h - $pad ?>" x2="<?= $w - $pad ?>" y2="<?= $h - $pad ?>" stroke="var(--line-strong)" />
    <?php foreach ($series as $i => $s):
        $x = $pad + $slot * $i + $slot / 2;
        $incH = ($s['income'] / $max) * $plotH;
        $costH = ($s['cost'] / $max) * $plotH;
        $baseY = $h - $pad;
    ?>
        <rect x="<?= round($x - $barW - 1, 1) ?>" y="<?= round($baseY - $incH, 1) ?>" width="<?= $barW ?>" height="<?= round($incH, 1) ?>" fill="var(--teal)" rx="2">
            <title><?= e($s['month']) ?> income: <?= e(number_format($s['income'])) ?></title>
        </rect>
        <rect x="<?= round($x + 1, 1) ?>" y="<?= round($baseY - $costH, 1) ?>" width="<?= $barW ?>" height="<?= round($costH, 1) ?>" fill="var(--blue)" rx="2">
            <title><?= e($s['month']) ?> cost: <?= e(number_format($s['cost'])) ?></title>
        </rect>
        <text x="<?= round($x, 1) ?>" y="<?= $h - $pad + 14 ?>" text-anchor="middle" font-size="9" fill="var(--text-faint)">
            <?= e(substr($s['month'], 5)) ?>
        </text>
    <?php endforeach; ?>
</svg>
</div>
<p class="small muted">
    <span style="display:inline-block;width:10px;height:10px;background:var(--teal);border-radius:2px"></span> Income
    &nbsp;·&nbsp;
    <span style="display:inline-block;width:10px;height:10px;background:var(--blue);border-radius:2px"></span> Cost
    — trailing 12 months
</p>
