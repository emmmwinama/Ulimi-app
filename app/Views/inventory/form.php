<?php
/** @var array<string,mixed>|null $item @var list<string> $categories */
$this->layout('layouts/app');
$editing = $item !== null;
$action = $editing ? url('inventory/' . rawurlencode((string) $item['id'])) : url('inventory');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($item[$k] ?? $d);
$label = static fn (string $c): string => ucwords(str_replace('_', ' ', $c));
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit stock item' : 'Add stock item' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Item name', 'required' => true, 'value' => $val('name'), 'placeholder' => 'e.g. Maize grain, Urea']) ?>
        <div class="grid cols-2">
            <div class="field">
                <label for="f_category">Category</label>
                <select class="select" id="f_category" name="category">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c) ?>" <?= (string) ($val('category') ?: 'other') === $c ? 'selected' : '' ?>><?= e($label($c)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('partials/field', ['name' => 'unit', 'label' => 'Unit', 'required' => true, 'value' => $val('unit', 'kg'), 'placeholder' => 'kg, bags, L']) ?>
        </div>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'quantity', 'label' => 'Quantity in stock', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('quantity')]) ?>
            <?= $this->partial('partials/field', ['name' => 'acquisition_unit_cost', 'label' => 'Acquisition cost per unit', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('acquisition_unit_cost')]) ?>
        </div>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'acquired_at', 'label' => 'Acquired on', 'type' => 'date', 'value' => $val('acquired_at')]) ?>
            <?= $this->partial('partials/field', ['name' => 'unit_weight', 'label' => 'Weight per unit (kg)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('unit_weight'), 'hint' => 'For bags/crates']) ?>
        </div>
        <?= $this->partial('partials/field', ['name' => 'season', 'label' => 'Season (optional)', 'value' => $val('season')]) ?>
        <div class="field">
            <label for="f_notes">Notes</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('inventory')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add item' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
