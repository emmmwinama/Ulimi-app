<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Money;
$rows = $dashboard['trends']['break_even'];
?>
<div class="card mb-16px" style="background:var(--teal-pale);border:1.5px solid var(--teal)">
    <div class="card-body">
        <p class="eyebrow" style="color:var(--teal);margin-bottom:6px">How break-even price is calculated</p>
        <p class="small" style="color:#166534;line-height:1.6">
            Break-even price = Total cost ÷ kg harvested. This is the minimum price per kg you must sell at
            to cover all costs. Compare it to market prices to see if you're operating profitably.
        </p>
    </div>
</div>

<?php if ($rows === []): ?>
    <div class="empty">
        <div class="h3">No break-even data</div>
        <p>Add yield and cost records first.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead><tr>
                <th>Crop</th><th>Field</th><th>Season</th><th class="num">Area</th><th class="num">Total cost</th>
                <th class="num">Yield</th><th class="num">Cost/ha</th><th class="num">Break-even/kg</th><th class="num">Revenue</th><th>Profitable</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $b): ?>
                <tr>
                    <td style="font-weight:800"><?= e($b['crop_name']) ?></td>
                    <td class="small muted"><?= e($b['field_name']) ?></td>
                    <td class="small" style="font-weight:700"><?= e($b['season']) ?></td>
                    <td class="num small"><?= e(number_format($b['area_planted'], 2)) ?> ha</td>
                    <td class="num" style="color:var(--red-text);font-weight:700"><?= e(Money::format($b['total_cost'])) ?></td>
                    <td class="num" style="color:var(--teal);font-weight:700"><?= $b['total_yield_kg'] > 0 ? e(number_format($b['total_yield_kg'])) . ' kg' : '—' ?></td>
                    <td class="num small"><?= e(Money::format($b['cost_per_ha'])) ?></td>
                    <td class="num">
                        <?php if ($b['break_even_price_per_kg'] !== null): ?>
                            <span style="font-weight:900;color:var(--sky-600)"><?= e(Money::format($b['break_even_price_per_kg'])) ?>/kg</span>
                        <?php else: ?>
                            <span class="small muted">No yield</span>
                        <?php endif; ?>
                    </td>
                    <td class="num" style="font-weight:700;color:<?= $b['revenue'] > 0 ? 'var(--green-text)' : 'var(--text-faint)' ?>">
                        <?= $b['revenue'] > 0 ? e(Money::format($b['revenue'])) : '—' ?>
                    </td>
                    <td>
                        <?php if ($b['total_yield_kg'] == 0): ?>
                            <span class="badge">No yield</span>
                        <?php elseif ($b['is_profitable']): ?>
                            <span class="row" style="gap:4px;color:var(--green-text);font-weight:700;font-size:.75rem">
                                <?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico ico-sm']) ?> Yes
                            </span>
                        <?php else: ?>
                            <span class="row" style="gap:4px;color:var(--red-text);font-weight:700;font-size:.75rem">
                                <?= $this->partial('partials/icon', ['name' => 'trend-down', 'class' => 'ico ico-sm']) ?> No
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
