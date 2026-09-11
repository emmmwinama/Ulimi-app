<?php
/**
 * Shared field-set for the Add/Edit employee form — included by both the
 * standalone form page and the index page's slide-over panels so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $employee
 */
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($employee[$k] ?? $d);
$uid = $employee['id'] ?? 'new';
?>
<?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Full name', 'required' => true, 'value' => $val('name'), 'idSuffix' => $uid]) ?>
<?= $this->partial('partials/field', ['name' => 'role', 'label' => 'Role', 'required' => true, 'value' => $val('role'), 'placeholder' => 'e.g. Foreman, Casual, Tractor operator', 'idSuffix' => $uid]) ?>
<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'pay_rate', 'label' => 'Pay rate', 'type' => 'number', 'step' => 'any',
        'inputmode' => 'decimal', 'required' => true, 'value' => $val('pay_rate'), 'idSuffix' => $uid,
    ]) ?>
    <div class="field">
        <label for="f_pay_rate_unit_<?= e((string) $uid) ?>">Per</label>
        <select class="select" id="f_pay_rate_unit_<?= e((string) $uid) ?>" name="pay_rate_unit">
            <?php foreach (['day', 'hour', 'month', 'task'] as $u): ?>
                <option value="<?= e($u) ?>" <?= (string) ($val('pay_rate_unit') ?: 'day') === $u ? 'selected' : '' ?>><?= e($u) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?= $this->partial('partials/field', ['name' => 'phone', 'label' => 'Phone (optional)', 'type' => 'tel', 'value' => $val('phone'), 'idSuffix' => $uid]) ?>
<label class="checkline">
    <input type="checkbox" name="is_active" value="1" <?= (int) ($val('is_active', 1)) === 1 ? 'checked' : '' ?>>
    <span>Active</span>
</label>
