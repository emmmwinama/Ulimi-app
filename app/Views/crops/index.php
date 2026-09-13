<?php
/**
 * @var array<int,array<string,mixed>> $crops
 * @var array{total_area:float,active:int,due_soon:int} $stats
 * @var list<string> $seasons
 * @var array<int,array<string,mixed>> $allFields
 * @var list<string> $statuses
 * @var array{season:string,field_id:string,status:string} $filters
 * @var string $season
 * @var bool $archived
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;

$statusColors = [
    'Active'    => ['bg' => 'var(--green-050)', 'fg' => 'var(--green-text)'],
    'Harvested' => ['bg' => 'var(--blue-050)',  'fg' => '#1E40AF'],
    'Failed'    => ['bg' => 'var(--red-050)',   'fg' => 'var(--red-text)'],
    'Resting'   => ['bg' => '#F5F3FF',          'fg' => '#7C3AED'],
];
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1"><?= $archived ? 'Archived crops' : 'Crops' ?></h1>
        <p class="lede"><?= count($crops) ?> planting<?= count($crops) === 1 ? '' : 's' ?> · <?= $archived ? 'archived' : 'active' ?></p>
    </div>
    <div class="row">
        <?php if ($archived): ?>
            <a class="btn secondary" href="<?= e(url('crops')) ?>">View active</a>
        <?php else: ?>
            <a class="btn secondary" href="<?= e(url('crops?view=archived')) ?>">
                <?= $this->partial('partials/icon', ['name' => 'boxes', 'class' => 'ico']) ?> View archived
            </a>
        <?php endif; ?>
        <?php if ($canManage && !$archived): ?>
            <?php if ($fields === []): ?>
                <a class="btn" href="<?= e(url('fields/create')) ?>" title="Add a field first">
                    <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add a field first
                </a>
            <?php else: ?>
                <a class="btn" href="#add-crop">
                    <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add planting
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($crops !== [] || $filters['season'] !== '' || $filters['field_id'] !== '' || $filters['status'] !== ''): ?>
    <div class="grid cols-4 mb-16px">
        <div class="stat">
            <div class="label">Plantings</div>
            <div class="value"><?= count($crops) ?></div>
        </div>
        <div class="stat">
            <div class="label">Area planted</div>
            <div class="value" style="color:var(--blue)"><?= e(number_format($stats['total_area'], 2)) ?> ha</div>
        </div>
        <div class="stat">
            <div class="label">Active</div>
            <div class="value" style="color:var(--green-text)"><?= $stats['active'] ?></div>
        </div>
        <div class="stat">
            <div class="label">Due for harvest</div>
            <div class="value" style="color:<?= $stats['due_soon'] > 0 ? 'var(--red-text)' : 'var(--text)' ?>"><?= $stats['due_soon'] ?></div>
        </div>
    </div>
<?php endif; ?>

<form method="get" action="<?= e(url('crops')) ?>" class="row wrap mb-24px" style="gap:10px">
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:220px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="field_id" onchange="this.form.submit()" style="max-width:220px">
        <option value="">All fields</option>
        <?php foreach ($allFields as $f): ?>
            <option value="<?= e((string) $f['id']) ?>" <?= $filters['field_id'] === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="status" onchange="this.form.submit()" style="max-width:180px">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($archived): ?><input type="hidden" name="view" value="archived"><?php endif; ?>
    <?php if ($filters['season'] !== '' || $filters['field_id'] !== '' || $filters['status'] !== ''): ?>
        <a class="btn ghost sm" href="<?= e(url('crops') . ($archived ? '?view=archived' : '')) ?>">Clear</a>
    <?php endif; ?>
    <noscript><button class="btn sm" type="submit">Filter</button></noscript>
</form>

<?php if ($crops === []): ?>
    <div class="empty">
        <span class="icon-box lg teal" style="margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'sprout', 'class' => 'ico']) ?>
        </span>
        <div class="h3">Nothing here</div>
        <p><?= $archived ? 'No archived plantings.' : 'Record what’s planted where — crop, variety, season and dates.' ?></p>
        <?php if ($canManage && !$archived && $fields !== []): ?>
            <p class="mt-16px"><a class="btn" href="#add-crop">Add a planting</a></p>
        <?php elseif ($canManage && !$archived): ?>
            <p class="mt-16px"><a class="btn" href="<?= e(url('fields/create')) ?>">Add a field first</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($crops as $c):
            $status = (string) $c['status'];
            $sc = $statusColors[$status] ?? ['bg' => 'var(--surface-3)', 'fg' => 'var(--text-soft)'];
            $isArchived = (int) $c['is_archived'] === 1;
            $daysToHarvest = null;
            if (!$isArchived && !empty($c['expected_harvest_date'])) {
                $daysToHarvest = (int) floor((strtotime((string) $c['expected_harvest_date']) - strtotime('today')) / 86400);
            }
            $details = [
                ['icon' => 'map', 'label' => 'Field', 'value' => (string) $c['field_name']],
                ['icon' => 'gauge', 'label' => 'Season', 'value' => (string) ($c['season'] ?: '—')],
                ['icon' => 'sprout', 'label' => 'Area', 'value' => number_format((float) $c['area_planted'], 2) . ' ha'],
                ['icon' => 'gauge', 'label' => 'Planted', 'value' => Dates::forDisplay((string) $c['planting_date'])],
            ];
        ?>
            <div class="card" style="display:flex;flex-direction:column;<?= $isArchived ? 'opacity:.85' : '' ?>">
                <?php if ($isArchived): ?>
                    <div class="row" style="gap:6px;padding:8px 16px;background:#F5F3FF;border-bottom:1px solid #DDD6FE;border-radius:var(--radius-lg) var(--radius-lg) 0 0">
                        <?= $this->partial('partials/icon', ['name' => 'boxes', 'class' => 'ico ico-sm']) ?>
                        <span style="font-size:.625rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#7C3AED">Archived</span>
                        <?php if (!empty($c['archived_reason'])): ?>
                            <span style="font-size:.625rem;color:#9333EA">· <?= e((string) $c['archived_reason']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="card-body" style="flex:1">
                    <div class="spread" style="align-items:flex-start;margin-bottom:12px">
                        <div class="row" style="gap:10px">
                            <span class="icon-box teal">
                                <?= $this->partial('partials/icon', ['name' => 'sprout', 'class' => 'ico']) ?>
                            </span>
                            <div>
                                <a href="<?= e(url('crops/' . rawurlencode((string) $c['id']))) ?>"><h3 class="h3"><?= e((string) $c['crop_name']) ?></h3></a>
                                <p style="font-size:.75rem;color:var(--text-faint)"><?= e((string) ($c['variety'] ?: '—')) ?></p>
                            </div>
                        </div>
                        <span class="badge" style="background:<?= $sc['bg'] ?>;color:<?= $sc['fg'] ?>"><?= e($status) ?></span>
                    </div>

                    <div class="grid cols-2" style="gap:8px;margin-bottom:12px">
                        <?php foreach ($details as $d): ?>
                            <div class="mini-stat">
                                <p class="label"><?= e($d['label']) ?></p>
                                <p style="font-size:.75rem;font-weight:800;color:var(--text)" class="nowrap"><?= e($d['value']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($daysToHarvest !== null):
                        $overdue = $daysToHarvest < 0;
                        $soon = $daysToHarvest >= 0 && $daysToHarvest < 14;
                        $barBg = $overdue ? 'var(--red-050)' : ($soon ? '#F0F9FF' : 'var(--teal-pale)');
                        $barFg = $overdue ? 'var(--red-text)' : ($soon ? '#0284C7' : 'var(--teal)');
                    ?>
                        <div style="background:<?= $barBg ?>;border-radius:12px;padding:8px 12px">
                            <p style="font-size:.75rem;font-weight:800;color:<?= $barFg ?>">
                                <?php if ($overdue): ?>
                                    Harvest overdue by <?= abs($daysToHarvest) ?> days
                                <?php elseif ($daysToHarvest === 0): ?>
                                    Harvest due today
                                <?php else: ?>
                                    Harvest in <?= $daysToHarvest ?> days · <?= e(Dates::forDisplay((string) $c['expected_harvest_date'])) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php elseif ($isArchived && !empty($c['archived_at'])): ?>
                        <p style="font-size:.75rem;color:var(--text-faint)">Archived <?= e(Dates::forDisplay((string) $c['archived_at'])) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($canManage): ?>
                    <div class="spread card-foot">
                        <p style="font-size:.75rem;color:var(--text-faint)"><?= e(Dates::forDisplay((string) $c['planting_date'])) ?></p>
                        <div class="row" style="gap:4px">
                            <?php if (!$isArchived): ?>
                                <details class="chip-select">
                                    <summary style="min-height:auto;height:28px;padding:0 10px;border-radius:9px;background:#F5F3FF;color:#7C3AED;border:1px solid #DDD6FE;font-size:.7rem;gap:4px">
                                        <?= $this->partial('partials/icon', ['name' => 'boxes', 'class' => 'ico ico-sm']) ?> Archive
                                    </summary>
                                    <div class="menu" style="right:0;left:auto;min-width:220px">
                                        <form method="post" action="<?= e(url('crops/' . rawurlencode((string) $c['id']) . '/archive')) ?>" style="padding:8px;display:flex;flex-direction:column;gap:8px">
                                            <?= csrf_field() ?>
                                            <input class="input" style="height:36px" name="reason" placeholder="Reason (optional)">
                                            <button type="submit" class="btn sm block">Confirm archive</button>
                                        </form>
                                    </div>
                                </details>
                                <a href="#edit-crop-<?= e((string) $c['id']) ?>" title="Edit"
                                   class="icon-box sm muted">
                                    <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                                </a>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('crops/' . rawurlencode((string) $c['id']) . '/restore')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="row" style="gap:4px;height:28px;padding:0 10px;border-radius:9px;background:var(--green-050);color:var(--green-text);border:0;cursor:pointer;font-size:.7rem;font-weight:700">
                                        <?= $this->partial('partials/icon', ['name' => 'check', 'class' => 'ico ico-sm']) ?> Restore
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage && !$archived && $fields !== []): ?>
    <div class="slide-over" id="add-crop">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Add crop planting</h2>
                    <p class="small muted mt-8px">Record what’s planted, where and when</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('crops')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/crops/crop', [
                        'crop' => null, 'fields' => $fields, 'cropTypes' => $cropTypes, 'currentSeason' => $currentSeason,
                    ]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add planting</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($crops as $c): if ((int) $c['is_archived'] === 1) continue; ?>
        <div class="slide-over" id="edit-crop-<?= e((string) $c['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit crop planting</h2>
                        <p class="small muted mt-8px">Update <?= e((string) $c['crop_name']) ?> on <?= e((string) $c['field_name']) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('crops/' . rawurlencode((string) $c['id']))) ?>" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/crops/crop', [
                            'crop' => $c, 'fields' => $fields, 'cropTypes' => $cropTypes, 'currentSeason' => $currentSeason,
                        ]) ?>
                    </div>
                    <div class="panel-foot">
                        <a href="#" class="btn ghost block">Cancel</a>
                        <button type="submit" class="btn block">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php $this->stop(); ?>
