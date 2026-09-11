<?php
/**
 * Shared field-set for the Record/Edit yield form — included by both the
 * standalone form page and the index page's slide-over panels so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $row
 * @var array<int,array<string,mixed>> $crops
 * @var list<string> $units
 * @var string $preselect
 */
$editing = $row !== null;
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($row[$k] ?? $d);
$uid = $editing ? (string) $row['id'] : ($preselect !== '' ? 'new-' . $preselect : 'new');
?>
<div class="field">
    <label for="f_crop_field_id_<?= e($uid) ?>">Crop planting <span aria-hidden="true" style="color:var(--red)">*</span></label>
    <select class="select" id="f_crop_field_id_<?= e($uid) ?>" name="crop_field_id" <?= $editing ? 'disabled' : 'required' ?>
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
    'value' => $val('harvest_date', date('Y-m-d')), 'idSuffix' => $uid,
]) ?>

<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'step' => 'any',
        'inputmode' => 'decimal', 'required' => true, 'value' => $val('quantity'), 'idSuffix' => $uid,
    ]) ?>
    <div class="field">
        <label for="f_unit_<?= e($uid) ?>">Unit</label>
        <select class="select" id="f_unit_<?= e($uid) ?>" name="unit">
            <?php foreach ($units as $u): ?>
                <option value="<?= e($u) ?>" <?= (string) ($val('unit') ?: 'kg') === $u ? 'selected' : '' ?>><?= e($u) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?= $this->partial('partials/field', [
    'name' => 'unit_weight', 'label' => 'Weight per unit (kg)', 'type' => 'number', 'step' => 'any',
    'inputmode' => 'decimal', 'value' => $val('unit_weight'), 'idSuffix' => $uid,
    'hint' => 'Only for bags/crates. Leave blank to use 50 kg/bag, 20 kg/crate.',
]) ?>

<div class="field">
    <label for="f_notes_<?= e($uid) ?>">Notes (optional)</label>
    <textarea class="textarea" id="f_notes_<?= e($uid) ?>" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
</div>
