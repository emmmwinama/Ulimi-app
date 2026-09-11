<?php
/**
 * Shared field-set for the Add/Edit field form — included by both the
 * standalone form page and the index page's slide-over panel so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $field
 * @var ?string $idSuffix disambiguates ids when this partial is rendered
 *                        more than once on a page (add panel + N edit panels)
 */
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($field[$k] ?? $d);
$soils = ['Sandy', 'Sandy loam', 'Loam', 'Clay loam', 'Clay', 'Silt', 'Silty clay', 'Peat', 'Alluvial'];
$uid = $idSuffix ?? ($field['id'] ?? 'new');
?>
<?= $this->partial('partials/field', [
    'name' => 'name', 'label' => 'Field name', 'required' => true,
    'value' => $val('name'), 'placeholder' => 'e.g. Kanyani', 'idSuffix' => $uid,
]) ?>

<div class="field">
    <label for="f_soil_type_<?= e((string) $uid) ?>">Soil type <span aria-hidden="true" style="color:var(--red)">*</span></label>
    <input class="input" list="soil_list_<?= e((string) $uid) ?>" id="f_soil_type_<?= e((string) $uid) ?>" name="soil_type" required
           value="<?= e((string) $val('soil_type')) ?>"
           <?= error_for('soil_type') ? 'aria-invalid="true"' : '' ?>>
    <datalist id="soil_list_<?= e((string) $uid) ?>">
        <?php foreach ($soils as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?>
    </datalist>
    <?php if ($er = error_for('soil_type')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
</div>

<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'total_area', 'label' => 'Total area (ha)', 'type' => 'number',
        'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('total_area'), 'idSuffix' => $uid,
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'cultivatable_area', 'label' => 'Cultivatable area (ha)', 'type' => 'number',
        'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('cultivatable_area'), 'idSuffix' => $uid,
    ]) ?>
</div>

<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'location_lat', 'label' => 'Latitude (optional)', 'type' => 'number',
        'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('location_lat'), 'idSuffix' => $uid,
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'location_lng', 'label' => 'Longitude (optional)', 'type' => 'number',
        'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('location_lng'), 'idSuffix' => $uid,
    ]) ?>
</div>

<div class="field">
    <label for="f_notes_<?= e((string) $uid) ?>">Notes (optional)</label>
    <textarea class="textarea" id="f_notes_<?= e((string) $uid) ?>" name="notes" rows="3"><?= e((string) $val('notes')) ?></textarea>
</div>
