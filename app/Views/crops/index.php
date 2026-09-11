<?php
/**
 * @var array<int,array<string,mixed>> $crops
 * @var list<string> $seasons
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
            <a class="btn" href="<?= e(url('crops/create')) ?>">
                <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add planting
            </a>
        <?php endif; ?>
    </div>
</div>

<form method="get" action="<?= e(url('crops')) ?>" class="row wrap mb-16" style="gap:10px">
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:240px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($archived): ?><input type="hidden" name="view" value="archived"><?php endif; ?>
    <noscript><button class="btn sm" type="submit">Filter</button></noscript>
</form>

<?php if ($crops === []): ?>
    <div class="empty">
        <span class="icon-box" style="width:64px;height:64px;border-radius:16px;background:var(--surface-2);color:var(--teal);display:grid;place-items:center;margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'sprout', 'class' => 'ico']) ?>
        </span>
        <div class="h3">Nothing here</div>
        <p><?= $archived ? 'No archived plantings.' : 'Record what’s planted where — crop, variety, season and dates.' ?></p>
        <?php if ($canManage && !$archived): ?>
            <p class="mt-16"><a class="btn" href="<?= e(url('crops/create')) ?>">Add a planting</a></p>
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
                            <span style="width:36px;height:36px;border-radius:12px;background:var(--teal-pale);color:var(--teal);display:grid;place-items:center;flex:none">
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
                            <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                                <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint);margin-bottom:2px"><?= e($d['label']) ?></p>
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
                    <div class="spread" style="padding:12px 20px;border-top:1px solid var(--line);background:var(--surface-2);border-radius:0 0 var(--radius-lg) var(--radius-lg)">
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
                                <a href="<?= e(url('crops/' . rawurlencode((string) $c['id']) . '/edit')) ?>" title="Edit"
                                   style="width:28px;height:28px;border-radius:9px;display:grid;place-items:center;background:var(--surface-3);color:var(--text-faint)">
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
<?php $this->stop(); ?>
