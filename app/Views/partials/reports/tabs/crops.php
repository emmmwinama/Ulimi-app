<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Money;
$crops = $dashboard['crops'];
?>
<?php if ($crops === []): ?>
    <div class="empty">
        <span class="icon-box lg muted" style="margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'sprout', 'class' => 'ico']) ?>
        </span>
        <div class="h3">No crops match the current filters</div>
    </div>
<?php else: ?>
    <div class="stack" style="--stack-gap:14px">
        <?php foreach ($crops as $c):
            $profit = $c['net_profit'];
        ?>
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="h3"><?= e($c['crop_name']) ?><?= $c['variety'] !== '' ? ' — ' . e($c['variety']) : '' ?></h3>
                        <p class="small muted">
                            <?= e($c['field_name']) ?> · <?= e($c['season']) ?> · <?= e(number_format($c['area_planted'], 2)) ?> ha
                            · Planted <?= e(\App\Support\Dates::forDisplay($c['planting_date'])) ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="small" style="font-weight:700;color:<?= $profit >= 0 ? 'var(--blue)' : 'var(--red-text)' ?>">Net <?= $profit >= 0 ? 'profit' : 'loss' ?></p>
                        <p style="font-size:1.1rem;font-weight:900;color:<?= $profit >= 0 ? 'var(--blue)' : 'var(--red-text)' ?>"><?= e(Money::format($profit)) ?></p>
                    </div>
                </div>
                <div class="grid cols-3" style="gap:0;border-top:1px solid var(--line)">
                    <?php
                    $cells = [
                        ['label' => 'Inputs', 'value' => $c['input_cost'], 'color' => 'var(--sky-600)'],
                        ['label' => 'Labour', 'value' => $c['labour_cost'], 'color' => '#9333EA'],
                        ['label' => 'Other', 'value' => $c['other_cost'], 'color' => '#0891B2'],
                        ['label' => 'Overhead (share)', 'value' => $c['allocated_overhead'], 'color' => 'var(--text-soft)'],
                        ['label' => 'Total cost', 'value' => $c['total_cost'], 'color' => 'var(--red-text)'],
                        ['label' => 'Revenue', 'value' => $c['revenue'], 'color' => 'var(--green-text)'],
                    ];
                    foreach ($cells as $cell): ?>
                        <div style="padding:12px 16px;border-top:1px solid var(--line);border-right:1px solid var(--line)">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px"><?= e($cell['label']) ?></p>
                            <p class="small" style="font-weight:800;color:<?= $cell['color'] ?>"><?= e(Money::format($cell['value'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($c['total_yield_kg'] > 0): ?>
                    <div class="row wrap" style="gap:24px;padding:12px 16px;border-top:1px solid var(--line);background:var(--surface-2)">
                        <?php
                        $stats = [
                            ['label' => 'Yield', 'value' => number_format($c['total_yield_kg']) . ' kg'],
                            ['label' => 'Yield/ha', 'value' => number_format($c['yield_per_ha']) . ' kg'],
                            ['label' => 'Cost/ha', 'value' => Money::format($c['cost_per_ha'])],
                            ['label' => 'Cost/kg', 'value' => Money::format($c['cost_per_kg'])],
                            ['label' => 'Activities', 'value' => (string) $c['activity_count']],
                        ];
                        foreach ($stats as $st): ?>
                            <div>
                                <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)"><?= e($st['label']) ?></p>
                                <p class="small" style="font-weight:800;color:var(--teal)"><?= e($st['value']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (count($crops) > 1): ?>
            <div class="card" style="background:var(--teal-pale);border:1.5px solid var(--teal)">
                <div class="card-body">
                    <p class="eyebrow" style="color:var(--teal)">Totals across <?= count($crops) ?> crops</p>
                    <div class="grid cols-3 mt-12px" style="gap:12px">
                        <?php
                        $totals = [
                            ['label' => 'Inputs', 'value' => array_sum(array_column($crops, 'input_cost')), 'color' => 'var(--sky-600)'],
                            ['label' => 'Labour', 'value' => array_sum(array_column($crops, 'labour_cost')), 'color' => '#9333EA'],
                            ['label' => 'Overhead', 'value' => array_sum(array_column($crops, 'allocated_overhead')), 'color' => 'var(--text-soft)'],
                            ['label' => 'Total cost', 'value' => array_sum(array_column($crops, 'total_cost')), 'color' => 'var(--red-text)'],
                            ['label' => 'Revenue', 'value' => array_sum(array_column($crops, 'revenue')), 'color' => 'var(--green-text)'],
                            ['label' => 'Net profit', 'value' => array_sum(array_column($crops, 'net_profit')), 'color' => null],
                        ];
                        foreach ($totals as $t):
                            $color = $t['color'] ?? ($t['value'] >= 0 ? 'var(--blue)' : 'var(--red-text)');
                        ?>
                            <div>
                                <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--teal)"><?= e($t['label']) ?></p>
                                <p class="small" style="font-weight:900;color:<?= $color ?>"><?= e(Money::format($t['value'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
