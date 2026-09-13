<?php
/**
 * @var array<string,mixed> $i
 * @var array<int,array<string,mixed>> $media
 * @var array<int,array<string,mixed>> $plantings
 * @var list<string> $types @var list<string> $severities @var list<string> $statuses
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
        <h1 class="h1"><?= e($label((string) $i['type'])) ?> — <?= e((string) $i['crop_name']) ?></h1>
        <p class="lede"><?= e((string) $i['field_name']) ?> · reported <?= e(Dates::forDisplay((string) $i['reported_date'])) ?></p>
    </div>
    <?php if ($canManage): ?>
        <div class="row" style="gap:8px">
            <a class="btn ghost" href="#edit-incident">Edit</a>
            <form method="post" action="<?= e(url('incidents/' . rawurlencode((string) $i['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this incident?')">
                <?= csrf_field() ?>
                <button class="btn ghost danger" type="submit">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="card mb-16px">
    <div class="card-body stack">
        <div class="row" style="gap:8px">
            <span class="badge <?= $sevBadge[$i['severity']] ?? '' ?>"><?= e($label((string) $i['severity'])) ?> severity</span>
            <span class="badge <?= $statusBadge[$i['status']] ?? '' ?>"><?= e($label((string) $i['status'])) ?></span>
        </div>
        <p><?= nl2br(e((string) $i['description'])) ?></p>
        <?php if (!empty($i['treatment_notes'])): ?>
            <div>
                <p class="eyebrow">Treatment notes</p>
                <p><?= nl2br(e((string) $i['treatment_notes'])) ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($i['referred_to'])): ?>
            <div>
                <p class="eyebrow">Referred to</p>
                <p><?= e((string) $i['referred_to']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Attachments</h2></div>
    <div class="card-body">
        <?php if ($media === []): ?>
            <p class="muted small">No photos or voice notes attached.</p>
        <?php else: ?>
            <div class="stack" style="--stack-gap:6px">
                <?php foreach ($media as $m): ?>
                    <div class="row spread small">
                        <span><?= e((string) $m['name']) ?> <span class="muted">(<?= e(Dates::forDisplay((string) $m['uploaded_at'])) ?>)</span></span>
                        <a class="btn sm ghost" href="<?= e(url('documents/' . rawurlencode((string) $m['id']) . '/download')) ?>">Download</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canManage): ?>
    <div class="slide-over" id="edit-incident">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Edit incident</h2>
                    <p class="small muted mt-8px"><?= e((string) $i['crop_name']) ?></p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('incidents/' . rawurlencode((string) $i['id']))) ?>" enctype="multipart/form-data" style="display:contents">
                <?= csrf_field() ?>
                <?= method_field('PUT') ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/incidents/incident', [
                        'i' => $i, 'plantings' => $plantings, 'types' => $types, 'severities' => $severities, 'statuses' => $statuses, 'media' => $media,
                    ]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Save changes</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
