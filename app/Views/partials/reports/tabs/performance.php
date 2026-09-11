<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Money;
$perf = $dashboard['trends']['crop_performance'];
$colors = ['#0D9488', '#10B981', '#3B82F6', '#8B5CF6', '#06B6D4', '#EF4444', '#EC4899', '#F59E0B'];
$trendBadge = [
    'improving' => ['icon' => 'trend-up', 'label' => 'Improving', 'color' => '#16A34A', 'bg' => '#ECFDF5'],
    'declining' => ['icon' => 'trend-down', 'label' => 'Declining', 'color' => '#DC2626', 'bg' => '#FEF2F2'],
    'stable'    => ['icon' => 'gauge', 'label' => 'Stable', 'color' => '#0284C7', 'bg' => '#F0F9FF'],
];
?>
<div class="card mb-16px" style="background:var(--teal-pale);border:1.5px solid var(--teal)">
    <div class="card-body">
        <p class="small" style="color:#166534">
            Performance summary across all seasons for each crop type. Trend is calculated by comparing
            yield/ha in the first recorded season against the most recent.
        </p>
    </div>
</div>

<?php if ($perf === []): ?>
    <div class="empty">
        <div class="h3">No crop performance data yet</div>
        <p>Add yield records first.</p>
    </div>
<?php else: ?>
    <div class="grid cols-2" style="gap:14px">
        <?php foreach ($perf as $i => $p): $color = $colors[$i % count($colors)]; $tb = $trendBadge[$p['trend']]; ?>
            <div class="card">
                <div class="card-body">
                    <div class="spread mb-12px">
                        <div class="row" style="gap:10px">
                            <span style="width:36px;height:36px;border-radius:12px;background:<?= $color ?>;color:#fff;display:grid;place-items:center;font-weight:900;flex:none"><?= e(mb_substr($p['crop_name'], 0, 1)) ?></span>
                            <div>
                                <p style="font-weight:800"><?= e($p['crop_name']) ?></p>
                                <p class="small muted"><?= $p['total_seasons'] ?> season<?= $p['total_seasons'] !== 1 ? 's' : '' ?></p>
                            </div>
                        </div>
                        <span class="badge" style="background:<?= $tb['bg'] ?>;color:<?= $tb['color'] ?>">
                            <?= $this->partial('partials/icon', ['name' => $tb['icon'], 'class' => 'ico ico-sm']) ?> <?= $tb['label'] ?>
                        </span>
                    </div>

                    <div class="grid cols-3" style="gap:8px;margin-bottom:12px">
                        <div style="background:var(--surface-2);border-radius:10px;padding:10px;text-align:center">
                            <p style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Avg yield/ha</p>
                            <p class="small" style="font-weight:800;color:var(--teal)"><?= e(number_format($p['avg_yield_per_ha'])) ?> kg</p>
                        </div>
                        <div style="background:var(--surface-2);border-radius:10px;padding:10px;text-align:center">
                            <p style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Avg cost/ha</p>
                            <p class="small" style="font-weight:800;color:var(--red-text)"><?= e(Money::format($p['avg_cost_per_ha'])) ?></p>
                        </div>
                        <div style="background:var(--surface-2);border-radius:10px;padding:10px;text-align:center">
                            <p style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Avg cost/kg</p>
                            <p class="small" style="font-weight:800;color:var(--sky-600)"><?= $p['avg_cost_per_kg'] > 0 ? e(Money::format($p['avg_cost_per_kg'])) : '—' ?></p>
                        </div>
                    </div>

                    <div class="grid cols-2" style="gap:8px;margin-bottom:12px">
                        <div style="background:var(--green-050);border:1px solid #86EFAC;border-radius:10px;padding:10px">
                            <p style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:#166534">Best season</p>
                            <p class="small" style="font-weight:800;color:#166534"><?= e($p['best_season']['season']) ?></p>
                            <p class="small" style="color:#16A34A"><?= e(number_format($p['best_season']['yield_per_ha'])) ?> kg/ha</p>
                        </div>
                        <div style="background:var(--red-050);border:1px solid #FCA5A5;border-radius:10px;padding:10px">
                            <p style="font-size:.6rem;font-weight:800;text-transform:uppercase;color:var(--red-text)">Lowest yield</p>
                            <p class="small" style="font-weight:800;color:var(--red-text)"><?= e($p['worst_season']['season']) ?></p>
                            <p class="small" style="color:var(--red-text)"><?= e(number_format($p['worst_season']['yield_per_ha'])) ?> kg/ha</p>
                        </div>
                    </div>

                    <table class="data" style="font-size:.75rem">
                        <thead><tr><th>Season</th><th class="num">Yield/ha</th><th class="num">Cost/ha</th><th class="num">Cost/kg</th></tr></thead>
                        <tbody>
                        <?php foreach ($p['seasons'] as $s): ?>
                            <tr>
                                <td style="font-weight:700"><?= e($s['season']) ?></td>
                                <td class="num" style="font-weight:800;color:var(--teal)"><?= e(number_format($s['yield_per_ha'])) ?> kg</td>
                                <td class="num" style="color:var(--red-text)"><?= e(Money::format($s['cost_per_ha'])) ?></td>
                                <td class="num" style="color:var(--sky-600)"><?= $s['cost_per_kg'] > 0 ? e(Money::format($s['cost_per_kg'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
