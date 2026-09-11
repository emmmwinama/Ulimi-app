<?php
/** @var array<string,mixed>|null $field */
$this->layout('layouts/app');
$editing = $field !== null;
$action  = $editing ? url('fields/' . rawurlencode((string) $field['id'])) : url('fields');
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div><h1 class="h1"><?= $editing ? 'Edit field' : 'Add field' ?></h1></div>
</div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/fields/fields', ['field' => $field]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('fields')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save changes' : 'Add field' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
