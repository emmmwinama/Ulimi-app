<?php
/**
 * Server-rendered multi-series line chart (one line per crop type, seasons
 * on the x-axis) — no JS, prints cleanly.
 *
 * @var list<array<string,mixed>> $series  each row: ['season' => string, <cropName> => float, ...]
 * @var list<string> $cropTypes
 * @var string $valueSuffix  appended to each value in tooltips (e.g. ' kg/ha')
 */
$series = $series ?? [];
$cropTypes = $cropTypes ?? [];
$valueSuffix = $valueSuffix ?? '';
$colors = ['#0D9488', '#10B981', '#3B82F6', '#8B5CF6', '#06B6D4', '#EF4444', '#EC4899', '#F59E0B'];

$max = 0.0;
foreach ($series as $row) {
    foreach ($cropTypes as $cn) {
        $max = max($max, (float) ($row[$cn] ?? 0));
    }
}
$max = $max > 0 ? $max : 1;

$w = 760;
$h = 280;
$pad = 34;
$plotW = $w - $pad * 2;
$plotH = $h - $pad * 2;
$n = max(1, count($series));
$stepX = $n > 1 ? $plotW / ($n - 1) : 0;
?>
<?php if ($series === [] || $cropTypes === []): ?>
    <div class="empty" style="padding:32px"><p class="small muted">Not enough data yet</p></div>
<?php else: ?>
<div class="table-wrap" style="border:0;overflow-x:auto">
<svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" style="min-width:640px;width:100%;height:auto">
    <?php for ($g = 0; $g <= 4; $g++): $gy = $pad + $plotH - ($g / 4) * $plotH; ?>
        <line x1="<?= $pad ?>" y1="<?= round($gy, 1) ?>" x2="<?= $w - $pad ?>" y2="<?= round($gy, 1) ?>" stroke="var(--line)" stroke-dasharray="3,3" />
        <text x="2" y="<?= round($gy, 1) + 3 ?>" font-size="9" fill="var(--text-faint)"><?= e(number_format($max * $g / 4)) ?></text>
    <?php endfor; ?>

    <?php foreach ($cropTypes as $ci => $cn): $color = $colors[$ci % count($colors)]; ?>
        <polyline fill="none" stroke="<?= $color ?>" stroke-width="2.5" points="<?php
            $pts = [];
            foreach ($series as $i => $row) {
                $x = $pad + $stepX * $i;
                $v = (float) ($row[$cn] ?? 0);
                $y = $pad + $plotH - ($v / $max) * $plotH;
                $pts[] = round($x, 1) . ',' . round($y, 1);
            }
            echo implode(' ', $pts);
        ?>" />
        <?php foreach ($series as $i => $row):
            $x = $pad + $stepX * $i;
            $v = (float) ($row[$cn] ?? 0);
            $y = $pad + $plotH - ($v / $max) * $plotH;
        ?>
            <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="4" fill="#fff" stroke="<?= $color ?>" stroke-width="2">
                <title><?= e($cn) ?> — <?= e((string) $row['season']) ?>: <?= e(number_format($v)) ?><?= e($valueSuffix) ?></title>
            </circle>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?php foreach ($series as $i => $row): $x = $pad + $stepX * $i; ?>
        <text x="<?= round($x, 1) ?>" y="<?= $h - 8 ?>" text-anchor="middle" font-size="9" fill="var(--text-faint)"><?= e((string) $row['season']) ?></text>
    <?php endforeach; ?>
</svg>
</div>
<p class="small muted row wrap" style="gap:12px">
    <?php foreach ($cropTypes as $ci => $cn): $color = $colors[$ci % count($colors)]; ?>
        <span><span style="display:inline-block;width:10px;height:10px;background:<?= $color ?>;border-radius:2px;margin-right:4px"></span><?= e($cn) ?></span>
    <?php endforeach; ?>
</p>
<?php endif; ?>
