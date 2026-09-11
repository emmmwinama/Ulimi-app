<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $fields
 * @var list<string> $types
 * @var array<string,string> $filters
 * @var bool $canManage
 * @var array<int,array{type:string,count:int,total_cost:float}> $byType
 * @var array<int,array{name:string,count:int,total_cost:float}> $byField
 * @var array<int,array{season:string,count:int,total_cost:float}> $bySeason
 * @var int $activeCount
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;

$total = array_sum(array_map(static fn ($r) => (float) $r['total_cost'], $rows));
$archivedCount = count($rows) - $activeCount;
$avgCost = $rows !== [] ? $total / count($rows) : 0.0;
$topType = $byType[0]['type'] ?? 'No activity';

$typeIcons = [
    'Planting' => 'sprout', 'Fertiliser Application' => 'wheat', 'Weeding' => 'leaf',
    'Irrigation' => 'sun', 'Harvesting' => 'boxes', 'Land Preparation' => 'gauge',
    'Pest & Disease Control' => 'alert', 'Crop Management' => 'sprout', 'Nursery' => 'leaf',
    'Monitoring' => 'info', 'Post-Harvest Handling' => 'boxes', 'Storage' => 'boxes', 'Transport' => 'map',
];

$groups = [];
foreach ($rows as $r) {
    $label = $r['crop_name'] ? ((string) $r['crop_name']) . (!empty($r['crop_variety']) ? ' — ' . $r['crop_variety'] : '') : 'General field work';
    $groups[$label] ??= [];
    $groups[$label][] = $r;
}
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Activities</h1>
        <p class="lede"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?> · <?= e(Money::format($total)) ?> total cost</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('activities/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Log activity
        </a>
    <?php endif; ?>
</div>

<?php if ($rows !== []): ?>
<div class="grid cols-4 mb-16px">
    <div class="stat">
        <div class="spread">
            <div>
                <div class="label">Shown records</div>
                <div class="value" style="font-size:1.4rem;color:var(--sky-600)"><?= count($rows) ?></div>
            </div>
            <span class="icon-box" style="background:var(--surface-2);color:var(--sky-600)"><?= $this->partial('partials/icon', ['name' => 'file-text', 'class' => 'ico']) ?></span>
        </div>
    </div>
    <div class="stat">
        <div class="spread">
            <div>
                <div class="label">Active crop work</div>
                <div class="value" style="font-size:1.4rem;color:var(--teal)"><?= $activeCount ?></div>
            </div>
            <span class="icon-box" style="background:var(--surface-2);color:var(--teal)"><?= $this->partial('partials/icon', ['name' => 'check-circle', 'class' => 'ico']) ?></span>
        </div>
    </div>
    <div class="stat">
        <div class="spread">
            <div>
                <div class="label">Archived history</div>
                <div class="value" style="font-size:1.4rem;color:var(--text-faint)"><?= $archivedCount ?></div>
            </div>
            <span class="icon-box" style="background:var(--surface-2);color:var(--text-faint)"><?= $this->partial('partials/icon', ['name' => 'boxes', 'class' => 'ico']) ?></span>
        </div>
    </div>
    <div class="stat">
        <div class="spread">
            <div>
                <div class="label">Average cost</div>
                <div class="value" style="font-size:1.4rem;color:var(--blue)"><?= e(Money::format($avgCost)) ?></div>
            </div>
            <span class="icon-box" style="background:var(--surface-2);color:var(--blue)"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico']) ?></span>
        </div>
    </div>
</div>

<details class="mb-16px" open>
    <summary class="row" style="cursor:pointer;font-weight:800;color:var(--text-soft);list-style:none;font-size:.875rem">
        <?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico ico-sm']) ?> Analytics
    </summary>
    <div class="grid cols-4 mt-12px">
        <div class="card"><div class="card-body">
            <p class="eyebrow mb-12px">By type</p>
            <div class="stack" style="--stack-gap:8px">
                <?php foreach (array_slice($byType, 0, 5) as $t): ?>
                    <div class="spread">
                        <div class="row" style="gap:8px">
                            <span style="width:28px;height:28px;border-radius:9px;background:var(--surface-2);color:var(--text-soft);display:grid;place-items:center;flex:none">
                                <?= $this->partial('partials/icon', ['name' => $typeIcons[$t['type']] ?? 'gauge', 'class' => 'ico ico-sm']) ?>
                            </span>
                            <span class="small" style="font-weight:700"><?= e($t['type']) ?></span>
                        </div>
                        <div class="text-right">
                            <div class="small" style="font-weight:800"><?= $t['count'] ?></div>
                            <div style="font-size:.7rem;color:var(--text-faint)"><?= e(Money::format($t['total_cost'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="eyebrow mb-12px">By field</p>
            <div class="stack" style="--stack-gap:8px">
                <?php foreach (array_slice($byField, 0, 5) as $f): ?>
                    <div class="spread">
                        <span class="small" style="font-weight:700"><?= e($f['name']) ?></span>
                        <div class="text-right">
                            <div class="small" style="font-weight:800"><?= $f['count'] ?></div>
                            <div style="font-size:.7rem;color:var(--text-faint)"><?= e(Money::format($f['total_cost'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="eyebrow mb-12px">By season</p>
            <div class="stack" style="--stack-gap:8px">
                <?php if ($bySeason === []): ?><p class="small muted">No seasons recorded.</p><?php endif; ?>
                <?php foreach (array_slice($bySeason, 0, 5) as $s): ?>
                    <div class="spread">
                        <span class="small" style="font-weight:700"><?= e($s['season']) ?></span>
                        <div class="text-right">
                            <div class="small" style="font-weight:800"><?= $s['count'] ?></div>
                            <div style="font-size:.7rem;color:var(--text-faint)"><?= e(Money::format($s['total_cost'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="eyebrow mb-12px">Cost shape</p>
            <p class="small mb-12px">Top work type: <strong><?= e($topType) ?></strong></p>
            <div class="stack" style="--stack-gap:8px">
                <?php foreach (array_slice($byType, 0, 4) as $t):
                    $pct = $total > 0 ? (int) round($t['total_cost'] / $total * 100) : 0;
                ?>
                    <div>
                        <div class="spread" style="font-size:.7rem;font-weight:700;color:var(--text-faint);margin-bottom:3px">
                            <span><?= e($t['type']) ?></span><span><?= $pct ?>%</span>
                        </div>
                        <div style="height:8px;background:var(--surface-3);border-radius:999px;overflow:hidden">
                            <div style="height:100%;width:<?= $pct ?>%;background:var(--sky-600);border-radius:999px"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>
    </div>
</details>
<?php endif; ?>

<form method="get" action="<?= e(url('activities')) ?>" class="row wrap mb-16px" style="gap:10px">
    <select class="select" name="field_id" onchange="this.form.submit()" style="max-width:200px">
        <option value="">All fields</option>
        <?php foreach ($fields as $f): ?>
            <option value="<?= e((string) $f['id']) ?>" <?= $filters['field_id'] === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="type" onchange="this.form.submit()" style="max-width:200px">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
    <input class="input" type="date" name="from" value="<?= e($filters['from']) ?>" style="max-width:160px" aria-label="From date">
    <input class="input" type="date" name="to" value="<?= e($filters['to']) ?>" style="max-width:160px" aria-label="To date">
    <button class="btn secondary sm" type="submit">Filter</button>
    <?php if (array_filter($filters)): ?><a class="btn ghost sm" href="<?= e(url('activities')) ?>">Clear</a><?php endif; ?>
</form>

<?php if ($rows === []): ?>
    <div class="empty">
        <span class="icon-box" style="width:64px;height:64px;border-radius:16px;background:var(--surface-2);color:var(--text-faint);display:grid;place-items:center;margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'file-text', 'class' => 'ico']) ?>
        </span>
        <div class="h3">No activities</div>
        <p>Log land prep, planting, spraying, weeding and harvest — with the labour, inputs and other costs behind each.</p>
        <?php if ($canManage): ?><p class="mt-16px"><a class="btn" href="<?= e(url('activities/create')) ?>">Log your first activity</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="stack" style="--stack-gap:20px">
        <?php foreach ($groups as $label => $groupRows): ?>
            <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:24px;padding:12px">
                <div style="padding:8px">
                    <p style="font-size:.875rem;font-weight:900;color:var(--text)"><?= e($label) ?></p>
                    <p style="font-size:.75rem;color:var(--text-faint)"><?= count($groupRows) ?> record<?= count($groupRows) === 1 ? '' : 's' ?> · <?= e(Money::format(array_sum(array_map(static fn ($a) => (float) $a['total_cost'], $groupRows)))) ?></p>
                </div>
                <div class="stack" style="--stack-gap:10px">
                    <?php foreach ($groupRows as $a):
                        $historical = (int) ($a['crop_archived'] ?? 0) === 1 || (string) ($a['crop_status'] ?? '') === 'Harvested';
                    ?>
                        <details class="card" style="border-radius:16px">
                            <summary class="row" style="list-style:none;cursor:pointer;padding:16px 20px;gap:16px;min-height:80px">
                                <span style="width:48px;height:48px;border-radius:16px;background:var(--surface-2);display:grid;place-items:center;flex:none">
                                    <?= $this->partial('partials/icon', ['name' => $typeIcons[$a['activity_type']] ?? 'gauge', 'class' => 'ico']) ?>
                                </span>
                                <div style="flex:1;min-width:0">
                                    <div class="row wrap" style="gap:6px">
                                        <span style="font-weight:800;color:var(--text)"><?= e((string) $a['activity_type']) ?></span>
                                        <?php if (!empty($a['season'])): ?><span class="badge blue" style="font-size:.625rem"><?= e((string) $a['season']) ?></span><?php endif; ?>
                                        <?php if ($historical): ?><span class="badge" style="font-size:.625rem">Historical</span><?php endif; ?>
                                    </div>
                                    <p style="font-size:.75rem;color:var(--text-faint)" class="nowrap">
                                        <?= e((string) $a['field_name']) ?><?= $a['crop_name'] ? ' — ' . e((string) $a['crop_name']) . ($a['crop_variety'] ? ' (' . e((string) $a['crop_variety']) . ')' : '') : '' ?>
                                    </p>
                                </div>
                                <div class="text-right" style="flex:none">
                                    <div style="font-weight:800;color:var(--text)"><?= e(Money::format((float) $a['total_cost'])) ?></div>
                                    <div style="font-size:.75rem;color:var(--text-faint)"><?= e(Dates::forDisplay((string) $a['date'])) ?></div>
                                </div>
                                <span style="color:var(--text-faint);flex:none" class="row-chevron">
                                    <?= $this->partial('partials/icon', ['name' => 'chevron-down', 'class' => 'ico ico-sm']) ?>
                                </span>
                            </summary>
                            <div style="padding:0 20px 16px;border-top:1px solid var(--line)">
                                <div class="grid cols-3 mt-12px" style="gap:8px">
                                    <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                                        <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Labour cost</p>
                                        <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e(Money::format((float) $a['labour_cost'])) ?></p>
                                    </div>
                                    <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                                        <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Input cost</p>
                                        <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e(Money::format((float) $a['input_cost'])) ?></p>
                                    </div>
                                    <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                                        <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Other costs</p>
                                        <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e(Money::format((float) $a['other_cost'])) ?></p>
                                    </div>
                                </div>
                                <?php if (!empty($a['responsible_person_name'])): ?>
                                    <div class="spread" style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:8px 12px;margin-top:8px">
                                        <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;color:var(--text-faint)">Responsible</span>
                                        <span style="font-size:.8rem;font-weight:700;color:var(--text)"><?= e((string) $a['responsible_person_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <p class="mt-12px"><a href="<?= e(url('activities/' . rawurlencode((string) $a['id']))) ?>" class="small" style="font-weight:700">View full breakdown →</a></p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
