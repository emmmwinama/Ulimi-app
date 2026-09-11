<?php
/**
 * @var array<string,mixed>|null $crop
 * @var array<int,array<string,mixed>> $fields
 * @var array<int,array<string,mixed>> $cropTypes
 * @var string $currentSeason
 */
$this->layout('layouts/app');
$editing = $crop !== null;
$action  = $editing ? url('crops/' . rawurlencode((string) $crop['id'])) : url('crops');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit crop planting' : 'Add crop planting' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/crops/crop', [
            'crop' => $crop, 'fields' => $fields, 'cropTypes' => $cropTypes, 'currentSeason' => $currentSeason,
        ]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('crops')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save changes' : 'Add planting' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
