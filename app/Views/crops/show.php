<?php
/**
 * @var array<string,mixed> $crop
 * @var list<array{stage:string,activity_type:string,start:string,end:string,state:string}> $timeline
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
$id = rawurlencode((string) $crop['id']);
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) $crop['crop_name']) ?><?= $crop['variety'] ? ' · ' . e((string) $crop['variety']) : '' ?></h1>
        <p class="lede"><?= e((string) $crop['field_name']) ?> · <?= e((string) $crop['season']) ?> ·
            <?= e(number_format((float) $crop['area_planted'], 2)) ?> ha</p>
    </div>
    <div class="row">
        <?php if ($canManage): ?>
            <a class="btn secondary" href="<?= e(url('crops/' . $id . '/edit')) ?>">Edit</a>
            <?php if ((int) $crop['is_archived'] === 0): ?>
                <details class="chip-select">
                    <summary>Archive</summary>
                    <div class="menu" style="min-width:280px;padding:12px">
                        <form method="post" action="<?= e(url('crops/' . $id . '/archive')) ?>" class="stack">
                            <?= csrf_field() ?>
                            <label class="label">Reason (optional)</label>
                            <input class="input" name="reason" placeholder="e.g. Crop failed — drought">
                            <button class="btn danger sm" type="submit">Archive planting</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <form method="post" action="<?= e(url('crops/' . $id . '/restore')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn secondary" type="submit">Restore</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ((int) $crop['is_archived'] === 1): ?>
    <div class="alert warning mb-16px">
        <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
        <div>Archived <?= e(Dates::forDisplay((string) $crop['archived_at'])) ?><?= $crop['archived_reason'] ? ' — ' . e((string) $crop['archived_reason']) : '' ?>.</div>
    </div>
<?php endif; ?>

<div class="grid cols-4 mb-24px">
    <div class="stat"><div class="label">Planted</div><div class="value" style="font-size:1.05rem"><?= e(Dates::forDisplay((string) $crop['planting_date'])) ?></div></div>
    <div class="stat"><div class="label">Expected harvest</div><div class="value" style="font-size:1.05rem"><?= e(Dates::forDisplay((string) $crop['expected_harvest_date'])) ?></div></div>
    <div class="stat"><div class="label">Status</div><div class="value" style="font-size:1.05rem"><?= e((string) $crop['status']) ?></div></div>
    <div class="stat"><div class="label">Lot ID</div><div class="value mono" style="font-size:.9rem"><?= e(substr((string) $crop['id'], -10)) ?></div></div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Crop calendar</h2><span class="small muted">Indicative — adjust to your conditions</span></div>
    <div class="card-body">
        <?php if ($timeline === []): ?>
            <p class="muted">No built-in calendar for this crop yet. Log activities directly from the Activities module.</p>
        <?php else: ?>
            <ol style="list-style:none;padding:0;margin:0">
                <?php foreach ($timeline as $st): ?>
                    <?php
                    [$dot, $cls] = match ($st['state']) {
                        'done'    => ['●', 'green'],
                        'current' => ['◆', 'amber'],
                        default   => ['○', ''],
                    };
                    ?>
                    <li class="row" style="align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid var(--line)">
                        <span class="badge <?= e($cls) ?>" style="min-width:84px;justify-content:center">
                            <?= e($st['state']) ?>
                        </span>
                        <div class="flex-1">
                            <strong><?= e($st['stage']) ?></strong>
                            <div class="small muted">
                                <?= e(Dates::forDisplay($st['start'])) ?> – <?= e(Dates::forDisplay($st['end'])) ?>
                                · suggests <em><?= e($st['activity_type']) ?></em>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>
<?php $this->stop(); ?>
