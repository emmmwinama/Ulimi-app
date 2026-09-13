<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var list<string> $types
 * @var array<int,array<string,mixed>> $plantings
 * @var array<string,array<int,array<string,mixed>>> $mediaByEvent
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$label = static fn (string $s): string => ucfirst($s);
$typeIcon = ['drought' => 'sun', 'flood' => 'map', 'wind' => 'trend-up', 'frost' => 'info', 'hail' => 'alert', 'other' => 'info'];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Climate Events</h1>
        <p class="lede">Drought, flood, wind and other weather losses — evidence for insurance claims and lender packs.</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="#add-event"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Record an event</a>
    <?php endif; ?>
</div>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No climate events recorded</div><p>Log a drought, flood, storm or other weather event that affected your farm — it feeds straight into your insurance record pack.</p></div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($rows as $r): ?>
            <div class="card" style="display:flex;flex-direction:column">
                <div class="card-body" style="flex:1">
                    <div class="spread" style="align-items:flex-start;margin-bottom:12px">
                        <div class="row" style="gap:10px">
                            <span class="icon-box red">
                                <?= $this->partial('partials/icon', ['name' => $typeIcon[$r['event_type']] ?? 'info', 'class' => 'ico']) ?>
                            </span>
                            <div>
                                <h3 class="h3"><?= e($label((string) $r['event_type'])) ?></h3>
                                <p style="font-size:.75rem;color:var(--text-faint)">
                                    <?= e(Dates::forDisplay((string) $r['start_date'])) ?><?= !empty($r['end_date']) ? ' – ' . e(Dates::forDisplay((string) $r['end_date'])) : '' ?>
                                </p>
                            </div>
                        </div>
                        <span class="badge red">Loss</span>
                    </div>

                    <div class="mini-stat" style="margin-bottom:8px">
                        <p class="label">Estimated loss</p>
                        <p class="value" style="color:var(--red-text)"><?= $r['estimated_loss_amount'] !== null ? e(Money::format((float) $r['estimated_loss_amount'])) : '—' ?></p>
                    </div>

                    <?php if (!empty($r['crop_name']) || !empty($r['field_name'])): ?>
                        <p style="font-size:.75rem;color:var(--text-faint)"><?= e((string) ($r['crop_name'] ?? '')) ?><?= !empty($r['field_name']) ? ' — ' . e((string) $r['field_name']) : '' ?></p>
                    <?php endif; ?>
                </div>

                <div class="spread card-foot">
                    <span></span>
                    <?php if ($canManage): ?>
                        <div class="row" style="gap:4px">
                            <a href="#edit-event-<?= e((string) $r['id']) ?>" title="Edit"
                               class="icon-box sm muted">
                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                            </a>
                            <form method="post" action="<?= e(url('climate-events/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this event?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Delete"
                                        class="icon-box sm red" style="border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<p class="hint mt-16px">Recorded events show up automatically in your <a href="<?= e(url('reports/pack/insurance')) ?>">Insurance file</a>.</p>

<?php if ($canManage): ?>
    <div class="slide-over" id="add-event">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Record a climate event</h2>
                    <p class="small muted mt-8px">Evidence for insurance claims and lender packs</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('climate-events')) ?>" enctype="multipart/form-data" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/climate-events/event', ['e' => null, 'types' => $types, 'plantings' => $plantings, 'media' => []]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Record event</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($rows as $r): ?>
        <div class="slide-over" id="edit-event-<?= e((string) $r['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit climate event</h2>
                        <p class="small muted mt-8px"><?= e($label((string) $r['event_type'])) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('climate-events/' . rawurlencode((string) $r['id']))) ?>" enctype="multipart/form-data" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/climate-events/event', [
                            'e' => $r, 'types' => $types, 'plantings' => $plantings, 'media' => $mediaByEvent[(string) $r['id']] ?? [],
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
