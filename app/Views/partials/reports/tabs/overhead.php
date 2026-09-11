<?php
/** @var array<string,mixed> $dashboard */
use App\Support\Money;
$o = $dashboard['overhead'];
?>
<div class="grid cols-3 mb-16px">
    <div class="stat">
        <div class="label">Total overhead</div>
        <div class="value"><?= e(Money::format($o['total_overhead'])) ?></div>
    </div>
    <div class="stat">
        <div class="label">Allocated</div>
        <div class="value" style="color:var(--teal)"><?= e(Money::format($o['total_allocated'])) ?></div>
    </div>
    <div class="stat">
        <div class="label">Unallocated</div>
        <div class="value" style="color:<?= $o['total_unallocated'] > 0 ? 'var(--sky-600)' : 'var(--text-faint)' ?>"><?= e(Money::format($o['total_unallocated'])) ?></div>
    </div>
</div>

<div class="card mb-16px" style="background:var(--teal-pale);border:1.5px solid var(--teal)">
    <div class="card-body">
        <p class="eyebrow" style="color:var(--teal);margin-bottom:6px">How overhead is allocated</p>
        <p class="small" style="color:#166534;line-height:1.6">
            For each overhead expense, AgriVault identifies all crops that were actively growing on that date.
            The expense is split proportionally by area planted. This allocation is fixed and does not
            change when you switch between Active, Archived and Both filters.
        </p>
    </div>
</div>

<?php if ($o['per_crop'] !== []): ?>
    <div class="table-wrap">
        <div style="padding:14px 16px;background:var(--surface-2);border-bottom:1px solid var(--line)">
            <p class="small" style="font-weight:800">Overhead per crop</p>
        </div>
        <table class="data">
            <thead><tr><th>Crop</th><th>Field</th><th>Season</th><th class="num">Area (ha)</th><th class="num">Allocated overhead</th><th>Share of total</th></tr></thead>
            <tbody>
            <?php foreach ($o['per_crop'] as $c):
                $pct = $o['total_allocated'] > 0 ? min(100, $c['allocated_overhead'] / $o['total_allocated'] * 100) : 0.0;
            ?>
                <tr>
                    <td style="font-weight:800"><?= e($c['crop_name']) ?></td>
                    <td class="small muted"><?= e($c['field_name']) ?></td>
                    <td class="small" style="font-weight:700"><?= e($c['season']) ?></td>
                    <td class="num"><?= e(number_format($c['area_planted'], 2)) ?></td>
                    <td class="num" style="font-weight:800;color:var(--text-soft)"><?= e(Money::format($c['allocated_overhead'])) ?></td>
                    <td>
                        <div class="row" style="gap:8px;align-items:center">
                            <div style="flex:1;height:6px;background:var(--surface-3);border-radius:999px;overflow:hidden;min-width:60px">
                                <div style="height:100%;width:<?= $pct ?>%;background:var(--text-soft);border-radius:999px"></div>
                            </div>
                            <span class="small" style="font-weight:700;color:var(--text-faint);width:44px;text-align:right"><?= number_format($pct, 1) ?>%</span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
