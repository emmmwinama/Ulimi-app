<?php
/** @var array<string,mixed>|null $employee */
$this->layout('layouts/app');
$editing = $employee !== null;
$action = $editing ? url('employees/' . rawurlencode((string) $employee['id'])) : url('employees');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($employee[$k] ?? $d);
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit employee' : 'Add employee' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Full name', 'required' => true, 'value' => $val('name')]) ?>
        <?= $this->partial('partials/field', ['name' => 'role', 'label' => 'Role', 'required' => true, 'value' => $val('role'), 'placeholder' => 'e.g. Foreman, Casual, Tractor operator']) ?>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', [
                'name' => 'pay_rate', 'label' => 'Pay rate', 'type' => 'number', 'step' => 'any',
                'inputmode' => 'decimal', 'required' => true, 'value' => $val('pay_rate'),
            ]) ?>
            <div class="field">
                <label for="f_pay_rate_unit">Per</label>
                <select class="select" id="f_pay_rate_unit" name="pay_rate_unit">
                    <?php foreach (['day', 'hour', 'month', 'task'] as $u): ?>
                        <option value="<?= e($u) ?>" <?= (string) ($val('pay_rate_unit') ?: 'day') === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?= $this->partial('partials/field', ['name' => 'phone', 'label' => 'Phone (optional)', 'type' => 'tel', 'value' => $val('phone')]) ?>
        <label class="checkline">
            <input type="checkbox" name="is_active" value="1" <?= (int) ($val('is_active', 1)) === 1 ? 'checked' : '' ?>>
            <span>Active</span>
        </label>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('employees')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add employee' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
