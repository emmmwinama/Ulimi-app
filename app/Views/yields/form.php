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
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($row[$k] ?? $d);
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit yield' : 'Record yield' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <div class="field">
            <label for="f_crop_field_id">Crop planting <span aria-hidden="true" style="color:var(--red)">*</span></label>
            <select class="select" id="f_crop_field_id" name="crop_field_id" <?= $editing ? 'disabled' : 'required' ?>
                    <?= error_for('crop_field_id') ? 'aria-invalid="true"' : '' ?>>
                <option value="">Select…</option>
                <?php foreach ($crops as $c): ?>
                    <option value="<?= e((string) $c['id']) ?>" <?= $preselect === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= e((string) $c['crop_name']) ?> — <?= e((string) $c['field_name']) ?> (<?= e((string) $c['season']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($editing): ?><input type="hidden" name="crop_field_id" value="<?= e((string) $val('crop_field_id')) ?>"><?php endif; ?>
            <?php if ($er = error_for('crop_field_id')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
        </div>

        <?= $this->partial('partials/field', [
            'name' => 'harvest_date', 'label' => 'Harvest date', 'type' => 'date', 'required' => true,
            'value' => $val('harvest_date', date('Y-m-d')),
        ]) ?>

        <div class="grid cols-2">
            <?= $this->partial('partials/field', [
                'name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'step' => 'any',
                'inputmode' => 'decimal', 'required' => true, 'value' => $val('quantity'),
            ]) ?>
            <div class="field">
                <label for="f_unit">Unit</label>
                <select class="select" id="f_unit" name="unit">
                    <?php foreach ($units as $u): ?>
                        <option value="<?= e($u) ?>" <?= (string) ($val('unit') ?: 'kg') === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?= $this->partial('partials/field', [
            'name' => 'unit_weight', 'label' => 'Weight per unit (kg)', 'type' => 'number', 'step' => 'any',
            'inputmode' => 'decimal', 'value' => $val('unit_weight'),
            'hint' => 'Only for bags/crates. Leave blank to use 50 kg/bag, 20 kg/crate.',
        ]) ?>

        <div class="field">
            <label for="f_notes">Notes (optional)</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('yields')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Record yield' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
