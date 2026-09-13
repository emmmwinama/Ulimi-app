<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var list<string> $categories @var list<string> $statuses
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Money;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$statusBadge = ['active' => 'green', 'under_repair' => 'amber', 'retired' => ''];
$catIcon = ['tractor' => 'boxes', 'irrigation' => 'map', 'tool' => 'settings', 'vehicle' => 'boxes', 'other' => 'boxes'];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Equipment</h1>
        <p class="lede"><?= count($rows) ?> item<?= count($rows) === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="#add-equipment"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Add equipment</a>
    <?php endif; ?>
</div>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No equipment recorded</div><p>Track tractors, irrigation gear, tools and vehicles — with a maintenance history for each.</p></div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($rows as $r): ?>
            <div class="card" style="display:flex;flex-direction:column">
                <div class="card-body" style="flex:1">
                    <div class="spread" style="align-items:flex-start;margin-bottom:12px">
                        <div class="row" style="gap:10px">
                            <span class="icon-box teal">
                                <?= $this->partial('partials/icon', ['name' => $catIcon[$r['category']] ?? 'boxes', 'class' => 'ico']) ?>
                            </span>
                            <div>
                                <h3 class="h3"><?= e((string) $r['name']) ?></h3>
                                <p style="font-size:.75rem;color:var(--text-faint)"><?= e($label((string) $r['category'])) ?></p>
                            </div>
                        </div>
                        <span class="badge <?= $statusBadge[$r['status']] ?? '' ?>"><?= e($label((string) $r['status'])) ?></span>
                    </div>

                    <div class="grid cols-2" style="gap:8px">
                        <div class="mini-stat">
                            <p class="label">Maintenance logs</p>
                            <p class="value"><?= (int) $r['log_count'] ?></p>
                        </div>
                        <div class="mini-stat">
                            <p class="label">Maintenance cost</p>
                            <p class="value" style="color:var(--teal)"><?= e(Money::format((float) $r['maintenance_cost'])) ?></p>
                        </div>
                    </div>
                </div>

                <div class="spread card-foot">
                    <a href="<?= e(url('equipment/' . rawurlencode((string) $r['id']))) ?>" style="font-size:.75rem;font-weight:700;color:var(--teal)">View →</a>
                    <div class="row" style="gap:4px">
                        <?php if ($canManage): ?>
                            <a href="#edit-equipment-<?= e((string) $r['id']) ?>" title="Edit"
                               class="icon-box sm muted">
                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                            </a>
                            <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this equipment record?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Delete"
                                        class="icon-box sm red" style="border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage): ?>
    <div class="slide-over" id="add-equipment">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Add equipment</h2>
                    <p class="small muted mt-8px">Track a tractor, tool, or vehicle</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('equipment')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/equipment/equipment', ['item' => null, 'categories' => $categories, 'statuses' => $statuses]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add equipment</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($rows as $r): ?>
        <div class="slide-over" id="edit-equipment-<?= e((string) $r['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit equipment</h2>
                        <p class="small muted mt-8px"><?= e((string) $r['name']) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $r['id']))) ?>" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/equipment/equipment', ['item' => $r, 'categories' => $categories, 'statuses' => $statuses]) ?>
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
