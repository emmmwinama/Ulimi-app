<?php
/**
 * @var array<string,mixed> $dashboard
 * @var string $compareA
 * @var string $compareB
 * @var array{season:string,archived:string,field_id:string,crop_field_id:string,from:string,to:string} $filters
 */
use App\Support\Money;
$summaries = $dashboard['trends']['season_summaries'];
$seasonKeys = array_keys($summaries);
$a = $summaries[$compareA] ?? null;
$b = $summaries[$compareB] ?? null;
?>
<form method="get" action="<?= e(url('reports')) ?>" class="row wrap mb-24px" style="gap:16px;align-items:flex-end">
    <input type="hidden" name="tab" value="comparison">
    <?php foreach ($filters as $k => $v): ?>
        <?php if ($v !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
    <?php endforeach; ?>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">Season A</label>
        <select class="select" name="compare_a" onchange="this.form.submit()" style="max-width:220px">
            <?php foreach ($seasonKeys as $s): ?>
                <option value="<?= e($s) ?>" <?= $compareA === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="h3" style="color:var(--text-faint)">vs</div>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">Season B</label>
        <select class="select" name="compare_b" onchange="this.form.submit()" style="max-width:220px">
            <option value="">— select —</option>
            <?php foreach ($seasonKeys as $s): ?>
                <option value="<?= e($s) ?>" <?= $compareB === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($a === null || $b === null): ?>
    <div class="empty">
        <div class="h3">Pick two seasons to compare</div>
        <p>Not enough seasons with data yet, or no second season selected.</p>
    </div>
<?php else:
    $metrics = [
        ['label' => 'Crops',       'a' => $a['crop_count'],     'b' => $b['crop_count'],     'fmt' => 'int',   'higher' => true],
        ['label' => 'Area (ha)',   'a' => $a['area'],           'b' => $b['area'],           'fmt' => 'area',  'higher' => true],
        ['label' => 'Total cost',  'a' => $a['total_cost'],     'b' => $b['total_cost'],     'fmt' => 'money', 'higher' => false],
        ['label' => 'Revenue',     'a' => $a['revenue'],        'b' => $b['revenue'],        'fmt' => 'money', 'higher' => true],
        ['label' => 'Net profit',  'a' => $a['net_profit'],     'b' => $b['net_profit'],     'fmt' => 'money', 'higher' => true],
        ['label' => 'Total yield', 'a' => $a['total_yield_kg'], 'b' => $b['total_yield_kg'], 'fmt' => 'kg',    'higher' => true],
        ['label' => 'Yield / ha',  'a' => $a['yield_per_ha'],   'b' => $b['yield_per_ha'],   'fmt' => 'kg',    'higher' => true],
        ['label' => 'Cost / ha',   'a' => $a['cost_per_ha'],    'b' => $b['cost_per_ha'],    'fmt' => 'money', 'higher' => false],
    ];
    $fmt = static function (float $v, string $kind): string {
        return match ($kind) {
            'int'   => number_format($v),
            'area'  => number_format($v, 2),
            'money' => Money::format($v),
            'kg'    => number_format($v) . ' kg',
            default => (string) $v,
        };
    };
?>
    <div class="table-wrap">
        <div class="grid cols-4" style="background:var(--surface-2);border-bottom:1px solid var(--line)">
            <div style="padding:14px 16px;font-size:.6875rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Metric</div>
            <div style="padding:14px 16px;text-align:center">
                <p class="small" style="font-weight:800;color:var(--teal)"><?= e($compareA) ?></p>
                <p style="font-size:.7rem;color:var(--text-faint)"><?= e(implode(', ', array_slice($a['crop_types'], 0, 4)) ?: '—') ?></p>
            </div>
            <div style="padding:14px 16px;text-align:center">
                <p class="small" style="font-weight:800;color:#9333EA"><?= e($compareB) ?></p>
                <p style="font-size:.7rem;color:var(--text-faint)"><?= e(implode(', ', array_slice($b['crop_types'], 0, 4)) ?: '—') ?></p>
            </div>
            <div style="padding:14px 16px;text-align:center;font-size:.6875rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Change</div>
        </div>
        <?php foreach ($metrics as $m):
            $delta = $m['a'] - $m['b'];
            $deltaPct = $m['b'] != 0 ? ($delta / abs($m['b'])) * 100 : 0.0;
            $improved = $m['higher'] ? $delta > 0 : $delta < 0;
            $neutral = abs($deltaPct) < 1;
        ?>
            <div class="grid cols-4" style="border-bottom:1px solid var(--line)">
                <div style="padding:12px 16px;font-size:.8rem;font-weight:700;color:var(--text-soft)"><?= e($m['label']) ?></div>
                <div style="padding:12px 16px;text-align:center;font-weight:800;color:var(--teal)"><?= e($fmt($m['a'], $m['fmt'])) ?></div>
                <div style="padding:12px 16px;text-align:center;font-weight:800;color:#9333EA"><?= e($fmt($m['b'], $m['fmt'])) ?></div>
                <div style="padding:12px 16px;text-align:center">
                    <?php if ($neutral): ?>
                        <span class="small muted">No change</span>
                    <?php elseif ($improved): ?>
                        <span class="small" style="font-weight:700;color:var(--green-text)">↑ <?= number_format(abs($deltaPct), 1) ?>% better</span>
                    <?php else: ?>
                        <span class="small" style="font-weight:700;color:var(--red-text)">↓ <?= number_format(abs($deltaPct), 1) ?>% worse</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
