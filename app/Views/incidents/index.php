<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $plantings
 * @var list<string> $types @var list<string> $severities @var list<string> $statuses
 * @var array<string,array<int,array<string,mixed>>> $mediaByRow
 * @var array{type:string,status:string} $filters
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$sevBadge = ['mild' => '', 'moderate' => 'amber', 'severe' => 'red'];
$statusBadge = ['open' => 'red', 'treated' => 'amber', 'resolved' => 'green'];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Pest &amp; Disease Log</h1>
        <p class="lede"><?= count($rows) ?> incident<?= count($rows) === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($canManage): ?>
        <?php if ($plantings !== []): ?>
            <a class="btn" href="#add-incident"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Report incident</a>
        <?php else: ?>
            <a class="btn" href="<?= e(url('crops/create')) ?>">Add a crop planting first</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('incidents')) ?>" class="row mb-16px" style="gap:8px;flex-wrap:wrap">
    <select class="select" name="type" onchange="this.form.submit()">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e($label($t)) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($filters['type'] !== '' || $filters['status'] !== ''): ?>
        <a class="btn ghost sm" href="<?= e(url('incidents')) ?>">Clear</a>
    <?php endif; ?>
</form>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No incidents logged</div><p>Report a pest, disease or other crop issue as soon as you spot it.</p></div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($rows as $r): ?>
            <div class="card" style="display:flex;flex-direction:column">
                <div class="card-body" style="flex:1">
                    <div class="spread" style="align-items:flex-start;margin-bottom:12px">
                        <div class="row" style="gap:10px">
                            <span class="icon-box red">
                                <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
                            </span>
                            <div>
                                <h3 class="h3"><?= e((string) $r['crop_name']) ?></h3>
                                <p style="font-size:.75rem;color:var(--text-faint)"><?= e((string) $r['field_name']) ?></p>
                            </div>
                        </div>
                        <span class="badge <?= $statusBadge[$r['status']] ?? '' ?>"><?= e($label((string) $r['status'])) ?></span>
                    </div>

                    <div class="grid cols-2" style="gap:8px">
                        <div class="mini-stat">
                            <p class="label">Type</p>
                            <p class="value"><?= e($label((string) $r['type'])) ?></p>
                        </div>
                        <div class="mini-stat">
                            <p class="label">Severity</p>
                            <p><span class="badge <?= $sevBadge[$r['severity']] ?? '' ?>"><?= e($label((string) $r['severity'])) ?></span></p>
                        </div>
                    </div>
                </div>

                <div class="spread card-foot">
                    <p style="font-size:.75rem;color:var(--text-faint)">Reported <?= e(Dates::forDisplay((string) $r['reported_date'])) ?></p>
                    <div class="row" style="gap:4px">
                        <a href="<?= e(url('incidents/' . rawurlencode((string) $r['id']))) ?>" title="View" class="icon-box sm teal">
                            <?= $this->partial('partials/icon', ['name' => 'arrow-right', 'class' => 'ico']) ?>
                        </a>
                        <?php if ($canManage): ?>
                            <a href="#edit-incident-<?= e((string) $r['id']) ?>" title="Edit" class="icon-box sm muted">
                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico']) ?>
                            </a>
                            <form method="post" action="<?= e(url('incidents/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this incident?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Delete" class="icon-box sm red" style="border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico']) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage && $plantings !== []): ?>
    <div class="slide-over" id="add-incident">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Report an incident</h2>
                    <p class="small muted mt-8px">Log a pest, disease or other crop issue</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('incidents')) ?>" enctype="multipart/form-data" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/incidents/incident', [
                        'i' => null, 'plantings' => $plantings, 'types' => $types, 'severities' => $severities, 'statuses' => $statuses, 'media' => [],
                    ]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Report incident</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($rows as $r): ?>
        <div class="slide-over" id="edit-incident-<?= e((string) $r['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit incident</h2>
                        <p class="small muted mt-8px"><?= e((string) $r['crop_name']) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('incidents/' . rawurlencode((string) $r['id']))) ?>" enctype="multipart/form-data" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/incidents/incident', [
                            'i' => $r, 'plantings' => $plantings, 'types' => $types, 'severities' => $severities, 'statuses' => $statuses,
                            'media' => $mediaByRow[(string) $r['id']] ?? [],
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
