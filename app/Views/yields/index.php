<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var float $totalKg
 * @var bool $canManage
 * @var array<int,array<string,mixed>> $groups
 * @var array<int,array<string,mixed>> $byType
 * @var list<string> $seasons
 * @var string $season
 * @var int $margin
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$fmtKg = static fn (float $n): string => number_format($n) . ' kg';
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Yields</h1>
        <p class="lede"><?= count($groups) ?> crop record<?= count($groups) === 1 ? '' : 's' ?> · <?= $fmtKg($totalKg) ?> total</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="#add-yield">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Record yield
        </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('yields')) ?>" class="row wrap mb-24px" style="gap:8px">
    <a href="<?= e(url('yields')) ?>" class="btn <?= $season === '' ? '' : 'secondary' ?> sm"><?= 'All' ?></a>
    <?php foreach ($seasons as $s): ?>
        <a href="<?= e(url('yields?season=' . rawurlencode($s))) ?>" class="btn <?= $season === $s ? '' : 'secondary' ?> sm"><?= e($s) ?></a>
    <?php endforeach; ?>
    <span class="row" style="gap:6px;margin-left:auto">
        <label class="small muted" for="margin">Target margin</label>
        <input class="input" style="width:70px;height:36px" type="number" min="0" max="200" name="margin" id="margin" value="<?= $margin ?>">
        <?php if ($season !== ''): ?><input type="hidden" name="season" value="<?= e($season) ?>"><?php endif; ?>
        <button class="btn secondary sm" type="submit">%</button>
    </span>
</form>

<?php if ($byType !== []): ?>
    <div class="card mb-16px"><div class="card-body">
        <p class="eyebrow mb-16px">Yield by crop type</p>
        <div class="grid cols-3">
            <?php foreach ($byType as $t):
                $yieldPerHa = $t['total_area'] > 0 ? $t['total_yield_kg'] / $t['total_area'] : 0;
                $costPerKg = $t['total_yield_kg'] > 0 ? $t['total_cost'] / $t['total_yield_kg'] : 0;
            ?>
                <div style="background:var(--teal-pale);border:1px solid #86EFAC;border-radius:12px;padding:14px">
                    <div class="row" style="gap:8px;margin-bottom:10px">
                        <?= $this->partial('partials/icon', ['name' => 'wheat', 'class' => 'ico ico-sm']) ?>
                        <span style="font-weight:800;color:var(--text)"><?= e((string) $t['crop_name']) ?></span>
                    </div>
                    <div class="grid cols-2" style="gap:8px">
                        <div>
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Total yield</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= $fmtKg($t['total_yield_kg']) ?></p>
                        </div>
                        <div>
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Area</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e(number_format($t['total_area'], 1)) ?> ha</p>
                        </div>
                        <div>
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Yield / ha</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= $fmtKg($yieldPerHa) ?></p>
                        </div>
                        <div>
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Cost / kg</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e(Money::format($costPerKg)) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div></div>
<?php endif; ?>

<?php if ($groups === []): ?>
    <div class="empty">
        <span class="icon-box" style="width:64px;height:64px;border-radius:16px;background:var(--surface-2);color:var(--text-faint);display:grid;place-items:center;margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'wheat', 'class' => 'ico']) ?>
        </span>
        <div class="h3">No yield records yet</div>
        <p>Record harvests from the crops page or click below.</p>
        <?php if ($canManage): ?><p class="mt-16px"><a class="btn" href="#add-yield">Record a yield</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="stack" style="--stack-gap:16px">
        <?php
        $statusColors = [
            'Active'    => ['bg' => 'var(--green-050)', 'fg' => 'var(--green-text)'],
            'Harvested' => ['bg' => 'var(--blue-050)',  'fg' => '#1E3A8A'],
            'Failed'    => ['bg' => 'var(--red-050)',   'fg' => 'var(--red-text)'],
        ];
        foreach ($groups as $g):
            $sc = $statusColors[$g['status']] ?? ['bg' => 'var(--surface-3)', 'fg' => 'var(--text-soft)'];
        ?>
            <div class="card">
                <div class="spread" style="padding:18px 24px;border-bottom:1px solid var(--line);background:var(--surface-2);align-items:flex-start">
                    <div>
                        <div class="row wrap" style="gap:8px">
                            <h3 class="h3"><?= e((string) $g['crop_name']) ?></h3>
                            <span class="small muted"><?= e((string) $g['variety']) ?></span>
                            <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['fg'] ?>"><?= e((string) $g['status']) ?></span>
                        </div>
                        <p class="small muted mt-8px"><?= e((string) $g['field_name']) ?> · <?= e((string) $g['season']) ?> · <?= e(number_format($g['area_planted'], 1)) ?> ha</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="grid cols-4 mb-16px" style="gap:10px">
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Total cost</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--red-text)"><?= e(Money::format($g['total_cost'])) ?></p>
                        </div>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Cost / ha</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= $g['cost_per_ha'] !== null ? e(Money::format($g['cost_per_ha'])) : '—' ?></p>
                        </div>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Total yield</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--teal)"><?= $g['total_yield_kg'] > 0 ? $fmtKg($g['total_yield_kg']) : 'Not recorded' ?></p>
                        </div>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Yield / ha</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--teal)"><?= $g['yield_per_ha'] !== null ? $fmtKg($g['yield_per_ha']) : '—' ?></p>
                        </div>
                    </div>

                    <?php if ($g['total_yield_kg'] > 0): ?>
                        <div style="background:#F0F9FF;border:1.5px solid #BAE6FD;border-radius:12px;padding:14px;margin-bottom:16px">
                            <p style="font-size:.8rem;font-weight:800;color:#075985;margin-bottom:10px">Selling price suggestion at <?= $margin ?>% margin</p>
                            <div class="grid cols-3" style="gap:8px">
                                <?php
                                $suggest = [
                                    'Break-even / kg' => Money::format($g['break_even_per_kg']),
                                    'Break-even / 50kg bag' => Money::format($g['break_even_per_bag50']),
                                    'Break-even / tonne' => Money::format($g['break_even_per_tonne']),
                                    'Suggested / kg' => Money::format($g['suggested_per_kg']),
                                    'Suggested / 50kg bag' => Money::format($g['suggested_per_bag50']),
                                    'Projected profit' => Money::format($g['projected_profit']),
                                ];
                                foreach ($suggest as $label => $value):
                                ?>
                                    <div style="background:var(--surface);border:1px solid #BAE6FD;border-radius:10px;padding:8px 10px">
                                        <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:#0284C7;margin-bottom:2px"><?= e($label) ?></p>
                                        <p style="font-size:.75rem;font-weight:800;color:#075985"><?= e($value) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="spread mb-12px">
                        <p class="eyebrow">Harvest records</p>
                        <?php if ($canManage): ?>
                            <a href="#add-yield-<?= e((string) $g['crop_field_id']) ?>" class="small" style="font-weight:700;color:var(--teal)">
                                + Add harvest
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($g['harvests'] === []): ?>
                        <p class="small muted">No harvests recorded yet</p>
                    <?php else: ?>
                        <div class="stack" style="--stack-gap:8px">
                            <?php foreach ($g['harvests'] as $y): ?>
                                <div class="spread" style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px 14px">
                                    <div>
                                        <p style="font-size:.875rem;font-weight:800;color:var(--text)">
                                            <?= e(rtrim(rtrim((string) $y['quantity'], '0'), '.')) ?> <?= e((string) $y['unit']) ?>
                                            <span class="small muted"> (<?= number_format((float) $y['quantity_kg']) ?> kg)</span>
                                        </p>
                                        <p class="small muted"><?= e(Dates::forDisplay((string) $y['harvest_date'])) ?><?= !empty($y['notes']) ? ' · ' . e((string) $y['notes']) : '' ?></p>
                                    </div>
                                    <?php if ($canManage): ?>
                                        <div class="row" style="gap:4px">
                                            <a href="#edit-yield-<?= e((string) $y['id']) ?>" title="Edit"
                                               style="width:28px;height:28px;border-radius:9px;display:grid;place-items:center;background:var(--surface-3);color:var(--text-faint)">
                                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                                            </a>
                                            <form method="post" action="<?= e(url('yields/' . rawurlencode((string) $y['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this yield record?')">
                                                <?= csrf_field() ?>
                                                <button type="submit" title="Delete"
                                                        style="width:28px;height:28px;border-radius:9px;display:grid;place-items:center;background:var(--red-050);color:var(--red-text);border:0;cursor:pointer">
                                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage): ?>
    <div class="slide-over" id="add-yield">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Record yield</h2>
                    <p class="small muted mt-8px">Log a harvest against a crop planting</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('yields')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/yields/yield', ['row' => null, 'crops' => $allCrops, 'units' => $units, 'preselect' => '']) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Record yield</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($groups as $g): ?>
        <div class="slide-over" id="add-yield-<?= e((string) $g['crop_field_id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Add harvest</h2>
                        <p class="small muted mt-8px"><?= e((string) $g['crop_name']) ?> — <?= e((string) $g['field_name']) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('yields')) ?>" style="display:contents">
                    <?= csrf_field() ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/yields/yield', ['row' => null, 'crops' => $allCrops, 'units' => $units, 'preselect' => (string) $g['crop_field_id']]) ?>
                    </div>
                    <div class="panel-foot">
                        <a href="#" class="btn ghost block">Cancel</a>
                        <button type="submit" class="btn block">Record yield</button>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($g['harvests'] as $y): ?>
            <div class="slide-over" id="edit-yield-<?= e((string) $y['id']) ?>">
                <a href="#" class="scrim" aria-label="Close"></a>
                <div class="panel">
                    <div class="panel-head">
                        <div>
                            <h2 class="h3">Edit yield</h2>
                            <p class="small muted mt-8px"><?= e((string) $g['crop_name']) ?> — <?= e((string) $g['field_name']) ?></p>
                        </div>
                        <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                    </div>
                    <form method="post" action="<?= e(url('yields/' . rawurlencode((string) $y['id']))) ?>" style="display:contents">
                        <?= csrf_field() ?>
                        <?= method_field('PUT') ?>
                        <div class="panel-body stack">
                            <?= $this->partial('partials/yields/yield', ['row' => $y, 'crops' => $allCrops, 'units' => $units, 'preselect' => (string) $y['crop_field_id']]) ?>
                        </div>
                        <div class="panel-foot">
                            <a href="#" class="btn ghost block">Cancel</a>
                            <button type="submit" class="btn block">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>
<?php $this->stop(); ?>
