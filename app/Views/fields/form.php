<?php
/** @var array<string,mixed>|null $field */
$this->layout('layouts/app');
$editing = $field !== null;
$action  = $editing ? url('fields/' . rawurlencode((string) $field['id'])) : url('fields');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($field[$k] ?? $d);
$soils = ['Sandy', 'Sandy loam', 'Loam', 'Clay loam', 'Clay', 'Silt', 'Silty clay', 'Peat', 'Alluvial'];
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
        <?= $this->partial('partials/field', [
            'name' => 'name', 'label' => 'Field name', 'required' => true,
            'value' => $val('name'), 'placeholder' => 'e.g. Kanyani',
        ]) ?>

        <div class="field">
            <label for="f_soil_type">Soil type <span aria-hidden="true" style="color:var(--red)">*</span></label>
            <input class="input" list="soil_list" id="f_soil_type" name="soil_type" required
                   value="<?= e((string) $val('soil_type')) ?>"
                   <?= error_for('soil_type') ? 'aria-invalid="true"' : '' ?>>
            <datalist id="soil_list">
                <?php foreach ($soils as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?>
            </datalist>
            <?php if ($er = error_for('soil_type')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
        </div>

        <div class="grid cols-2">
            <?= $this->partial('partials/field', [
                'name' => 'total_area', 'label' => 'Total area (ha)', 'type' => 'number',
                'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('total_area'),
            ]) ?>
            <?= $this->partial('partials/field', [
                'name' => 'cultivatable_area', 'label' => 'Cultivatable area (ha)', 'type' => 'number',
                'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('cultivatable_area'),
            ]) ?>
        </div>

        <div class="grid cols-2">
            <?= $this->partial('partials/field', [
                'name' => 'location_lat', 'label' => 'Latitude (optional)', 'type' => 'number',
                'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('location_lat'),
            ]) ?>
            <?= $this->partial('partials/field', [
                'name' => 'location_lng', 'label' => 'Longitude (optional)', 'type' => 'number',
                'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('location_lng'),
            ]) ?>
        </div>

        <div class="field">
            <label for="f_notes">Notes (optional)</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="3"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('fields')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save changes' : 'Add field' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
