<?php
/**
 * Server-rendered stacked bar chart (expense breakdown by season) — no JS.
 *
 * @var list<array{season:string,input_cost:float,labour_cost:float,other_cost:float,overhead:float}> $series
 */
use App\Support\Money;
$series = $series ?? [];
$segments = [
    ['key' => 'input_cost', 'label' => 'Inputs', 'color' => '#0284C7'],
    ['key' => 'labour_cost', 'label' => 'Labour', 'color' => '#9333EA'],
    ['key' => 'other_cost', 'label' => 'Other', 'color' => '#0891B2'],
    ['key' => 'overhead', 'label' => 'Overhead', 'color' => '#64748B'],
];

$max = 0.0;
foreach ($series as $s) {
    $total = 0.0;
    foreach ($segments as $seg) {
        $total += (float) ($s[$seg['key']] ?? 0);
    }
    $max = max($max, $total);
}
$max = $max > 0 ? $max : 1;

$w = 760;
$h = 260;
$pad = 30;
$plotH = $h - $pad * 2;
$n = max(1, count($series));
$slot = ($w - $pad * 2) / $n;
$barW = min(48, $slot * 0.55);
?>
<?php if ($series === []): ?>
    <div class="empty" style="padding:32px"><p class="small muted">No expense data yet</p></div>
<?php else: ?>
<div class="table-wrap" style="border:0;overflow-x:auto">
<svg viewBox="0 0 <?= $w ?> <?= $h ?>" role="img" style="min-width:640px;width:100%;height:auto">
    <line x1="<?= $pad ?>" y1="<?= $h - $pad ?>" x2="<?= $w - $pad ?>" y2="<?= $h - $pad ?>" stroke="var(--line-strong)" />
    <?php foreach ($series as $i => $s):
        $x = $pad + $slot * $i + $slot / 2 - $barW / 2;
        $baseY = $h - $pad;
        $y = $baseY;
    ?>
        <?php foreach ($segments as $seg):
            $v = (float) ($s[$seg['key']] ?? 0);
            if ($v <= 0) continue;
            $segH = ($v / $max) * $plotH;
            $y -= $segH;
        ?>
            <rect x="<?= round($x, 1) ?>" y="<?= round($y, 1) ?>" width="<?= round($barW, 1) ?>" height="<?= round($segH, 1) ?>" fill="<?= $seg['color'] ?>">
                <title><?= e($seg['label']) ?> — <?= e($s['season']) ?>: <?= e(Money::format($v)) ?></title>
            </rect>
        <?php endforeach; ?>
        <text x="<?= round($x + $barW / 2, 1) ?>" y="<?= $h - $pad + 14 ?>" text-anchor="middle" font-size="9" fill="var(--text-faint)"><?= e($s['season']) ?></text>
    <?php endforeach; ?>
</svg>
</div>
<p class="small muted row wrap" style="gap:12px">
    <?php foreach ($segments as $seg): ?>
        <span><span style="display:inline-block;width:10px;height:10px;background:<?= $seg['color'] ?>;border-radius:2px;margin-right:4px"></span><?= e($seg['label']) ?></span>
    <?php endforeach; ?>
</p>
<?php endif; ?>
