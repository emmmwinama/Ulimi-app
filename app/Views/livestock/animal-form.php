<?php
/**
 * @var array<string,mixed>|null $a
 * @var array<int,array<string,mixed>> $types @var array<int,array<string,mixed>> $parents
 * @var list<string> $sexes @var list<string> $statuses @var list<string> $acqTypes
 */
$this->layout('layouts/app');
$editing = $a !== null;
$action = $editing ? url('livestock/animals/' . rawurlencode((string) $a['id'])) : url('livestock/animals');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit animal' : 'Add animal' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body">
        <?= $this->partial('partials/livestock/animal', [
            'a' => $a, 'types' => $types, 'parents' => $parents, 'sexes' => $sexes, 'statuses' => $statuses, 'acqTypes' => $acqTypes,
        ]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('livestock')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add animal' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
