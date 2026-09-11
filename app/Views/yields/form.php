<?php
/**
 * @var array<string,mixed>|null $row
 * @var array<int,array<string,mixed>> $crops
 * @var list<string> $units
 * @var string $preselect
 */
$this->layout('layouts/app');
$editing = $row !== null;
$action = $editing ? url('yields/' . rawurlencode((string) $row['id'])) : url('yields');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit yield' : 'Record yield' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/yields/yield', ['row' => $row, 'crops' => $crops, 'units' => $units, 'preselect' => $preselect]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('yields')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Record yield' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
