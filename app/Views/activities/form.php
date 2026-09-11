<?php
/**
 * @var array<string,mixed>|null $a       activity with labour/inputs/other, or null
 * @var array<int,array<string,mixed>> $fields
 * @var array<int,array<string,mixed>> $crops
 * @var array<int,array<string,mixed>> $employees
 * @var list<string> $types
 * @var bool $payroll
 */
$this->layout('layouts/app');
$editing = $a !== null;
$action = $editing ? url('activities/' . rawurlencode((string) $a['id'])) : url('activities');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit activity' : 'Log activity' ?></h1></div></div>

<form method="post" action="<?= e($action) ?>" class="stack" style="--stack-gap:20px" data-activity-form>
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <?= $this->partial('partials/activities/activity', [
        'a' => $a, 'fields' => $fields, 'crops' => $crops, 'employees' => $employees, 'types' => $types, 'payroll' => $payroll,
    ]) ?>
    <div class="spread">
        <a class="btn ghost" href="<?= e(url('activities')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save activity' : 'Log activity' ?></button>
    </div>
</form>
<?php $this->stop(); ?>
