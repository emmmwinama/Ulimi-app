<?php
/**
 * Shared field-set for the Add/Edit crop planting form — included by both
 * the standalone form page and the index page's slide-over panels so the
 * two never drift apart.
 *
 * @var array<string,mixed>|null $crop
 * @var array<int,array<string,mixed>> $fields
 * @var array<int,array<string,mixed>> $cropTypes
 * @var string $currentSeason
 */
$editing = $crop !== null;
$uid = $editing ? (string) $crop['id'] : 'new';
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($crop[$k] ?? $d);
?>
<div class="field">
    <label for="f_field_id_<?= e($uid) ?>">Field <span aria-hidden="true" style="color:var(--red)">*</span></label>
    <select class="select" id="f_field_id_<?= e($uid) ?>" name="field_id" <?= $editing ? 'disabled' : 'required' ?>
            <?= error_for('field_id') ? 'aria-invalid="true"' : '' ?>>
        <option value="">Select a field…</option>
        <?php foreach ($fields as $f): ?>
            <option value="<?= e((string) $f['id']) ?>" <?= (string) $val('field_id') === (string) $f['id'] ? 'selected' : '' ?>>
                <?= e((string) $f['name']) ?> (<?= e(number_format((float) $f['cultivatable_area'], 1)) ?> ha)
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($editing): ?>
        <input type="hidden" name="field_id" value="<?= e((string) $val('field_id')) ?>">
        <p class="hint">The field can’t be changed after a planting is created.</p>
    <?php endif; ?>
    <?php if ($er = error_for('field_id')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
</div>

<div class="grid cols-2">
    <div class="field">
        <label for="f_crop_type_<?= e($uid) ?>">Crop <span aria-hidden="true" style="color:var(--red)">*</span></label>
        <input class="input" list="croptype_list_<?= e($uid) ?>" id="f_crop_type_<?= e($uid) ?>" name="crop_type" required
               value="<?= e((string) ($val('crop_type') ?: $val('crop_name'))) ?>"
               <?= error_for('crop_type') ? 'aria-invalid="true"' : '' ?>>
        <datalist id="croptype_list_<?= e($uid) ?>">
            <?php foreach ($cropTypes as $ct): ?><option value="<?= e((string) $ct['name']) ?>"><?php endforeach; ?>
        </datalist>
        <?php if ($er = error_for('crop_type')): ?><p class="err"><?= e($er) ?></p>
        <?php else: ?><p class="hint">Pick a listed crop or type a new one.</p><?php endif; ?>
    </div>
    <?= $this->partial('partials/field', [
        'name' => 'variety', 'label' => 'Variety', 'value' => $val('variety'),
        'placeholder' => 'e.g. SC627', 'idSuffix' => $uid,
    ]) ?>
</div>

<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'area_planted', 'label' => 'Area planted (ha)', 'type' => 'number',
        'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('area_planted'), 'idSuffix' => $uid,
    ]) ?>
    <div class="field">
        <label for="f_season_<?= e($uid) ?>">Season <span aria-hidden="true" style="color:var(--red)">*</span></label>
        <input class="input" list="season_list_<?= e($uid) ?>" id="f_season_<?= e($uid) ?>" name="season" required
               value="<?= e((string) ($val('season') ?: $currentSeason)) ?>"
               <?= error_for('season') ? 'aria-invalid="true"' : '' ?>>
        <datalist id="season_list_<?= e($uid) ?>">
            <?php foreach (\App\Support\Seasons::suggestions() as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?>
        </datalist>
        <?php if ($er = error_for('season')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
    </div>
</div>

<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'planting_date', 'label' => 'Planting date', 'type' => 'date',
        'required' => true, 'value' => $val('planting_date'), 'idSuffix' => $uid,
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'expected_harvest_date', 'label' => 'Expected harvest', 'type' => 'date',
        'required' => true, 'value' => $val('expected_harvest_date'), 'idSuffix' => $uid,
    ]) ?>
</div>

<div class="field">
    <label for="f_status_<?= e($uid) ?>">Status</label>
    <select class="select" id="f_status_<?= e($uid) ?>" name="status">
        <?php foreach (['Active', 'Harvested', 'Failed', 'Terminated'] as $s): ?>
            <option value="<?= e($s) ?>" <?= (string) ($val('status') ?: 'Active') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
</div>
