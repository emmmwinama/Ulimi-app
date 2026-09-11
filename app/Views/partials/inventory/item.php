<?php
/**
 * Shared field-set for the Add/Edit stock item form — included by both the
 * standalone form page and the index page's slide-over panels so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $item
 * @var list<string> $categories
 */
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($item[$k] ?? $d);
$label = static fn (string $c): string => ucwords(str_replace('_', ' ', $c));
$uid = $item['id'] ?? 'new';
?>
<?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Item name', 'required' => true, 'value' => $val('name'), 'placeholder' => 'e.g. Maize grain, Urea', 'idSuffix' => $uid]) ?>
<div class="grid cols-2">
    <div class="field">
        <label for="f_category_<?= e((string) $uid) ?>">Category</label>
        <select class="select" id="f_category_<?= e((string) $uid) ?>" name="category">
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c) ?>" <?= (string) ($val('category') ?: 'other') === $c ? 'selected' : '' ?>><?= e($label($c)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?= $this->partial('partials/field', ['name' => 'unit', 'label' => 'Unit', 'required' => true, 'value' => $val('unit', 'kg'), 'placeholder' => 'kg, bags, L', 'idSuffix' => $uid]) ?>
</div>
<div class="grid cols-2">
    <?= $this->partial('partials/field', ['name' => 'quantity', 'label' => 'Quantity in stock', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('quantity'), 'idSuffix' => $uid]) ?>
    <?= $this->partial('partials/field', ['name' => 'acquisition_unit_cost', 'label' => 'Acquisition cost per unit', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('acquisition_unit_cost'), 'idSuffix' => $uid]) ?>
</div>
<div class="grid cols-2">
    <?= $this->partial('partials/field', ['name' => 'acquired_at', 'label' => 'Acquired on', 'type' => 'date', 'value' => $val('acquired_at'), 'idSuffix' => $uid]) ?>
    <?= $this->partial('partials/field', ['name' => 'unit_weight', 'label' => 'Weight per unit (kg)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('unit_weight'), 'hint' => 'For bags/crates', 'idSuffix' => $uid]) ?>
</div>
<?= $this->partial('partials/field', ['name' => 'season', 'label' => 'Season (optional)', 'value' => $val('season'), 'idSuffix' => $uid]) ?>
<div class="field">
    <label for="f_notes_<?= e((string) $uid) ?>">Notes</label>
    <textarea class="textarea" id="f_notes_<?= e((string) $uid) ?>" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
</div>
