<?php
/** @var array<string,mixed>|null $employee */
$this->layout('layouts/app');
$editing = $employee !== null;
$action = $editing ? url('employees/' . rawurlencode((string) $employee['id'])) : url('employees');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit employee' : 'Add employee' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/employees/employee', ['employee' => $employee]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('employees')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add employee' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
