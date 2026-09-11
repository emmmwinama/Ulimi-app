<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Dates;
$s = $dashboard['summary'];
$yields = $dashboard['yields'];
?>
<div class="grid cols-3 mb-16px">
    <div class="stat">
        <div class="label">Yield records</div>
        <div class="value" style="color:var(--teal)"><?= count($yields) ?></div>
    </div>
    <div class="stat">
        <div class="label">Total harvested</div>
        <div class="value" style="color:var(--blue)"><?= e(number_format($s['total_kg_harvested'])) ?> kg</div>
    </div>
    <div class="stat">
        <div class="label">Average yield/ha</div>
        <div class="value" style="color:var(--sky-600)"><?= $s['avg_yield_per_ha'] > 0 ? e(number_format($s['avg_yield_per_ha'])) . ' kg/ha' : '—' ?></div>
    </div>
</div>

<div class="table-wrap">
    <table class="data">
        <thead><tr><th>Crop</th><th>Field</th><th>Season</th><th>Harvest date</th><th>As recorded</th><th class="num">Total (kg)</th><th>Notes</th></tr></thead>
        <tbody>
        <?php if ($yields === []): ?>
            <tr><td colspan="7" style="text-align:center;padding:44px;color:var(--text-faint)">No yield records for current filters</td></tr>
        <?php else: foreach ($yields as $y): ?>
            <tr>
                <td style="font-weight:800"><?= e($y['crop_name']) ?></td>
                <td class="small muted"><?= e($y['field_name']) ?></td>
                <td class="small" style="font-weight:700"><?= e($y['season']) ?></td>
                <td class="small muted"><?= e(Dates::forDisplay($y['harvest_date'])) ?></td>
                <td style="font-weight:800;color:var(--teal)"><?= e($y['display_qty']) ?></td>
                <td class="num" style="font-weight:700;color:var(--blue)"><?= e(number_format($y['kg'])) ?></td>
                <td class="small muted" style="font-style:italic"><?= e((string) ($y['notes'] ?? '—')) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="row spread" style="padding:12px 16px;background:var(--surface-2);border-top:1px solid var(--line)">
        <p class="small muted"><?= count($yields) ?> record<?= count($yields) === 1 ? '' : 's' ?> · quantities shown as recorded, then converted to kg</p>
        <p class="small" style="font-weight:900;color:var(--blue)">Total: <?= e(number_format($s['total_kg_harvested'])) ?> kg</p>
    </div>
</div>
