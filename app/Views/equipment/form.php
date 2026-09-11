<?php
/**
 * @var array<string,mixed>|null $e
 * @var list<string> $categories @var list<string> $statuses
 */
$this->layout('layouts/app');
$editing = $e !== null;
$action = $editing ? url('equipment/' . rawurlencode((string) $e['id'])) : url('equipment');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($e[$k] ?? $d);
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit equipment' : 'Add equipment' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'required' => true, 'value' => $val('name'), 'placeholder' => 'e.g. John Deere tractor, Drip irrigation kit']) ?>
        <div class="grid cols-2">
            <div class="field">
                <label for="f_category">Category</label>
                <select class="select" id="f_category" name="category">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c) ?>" <?= (string) ($val('category') ?: 'other') === $c ? 'selected' : '' ?>><?= e($label($c)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f_status">Status</label>
                <select class="select" id="f_status" name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= (string) ($val('status') ?: 'active') === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'acquisition_date', 'label' => 'Acquired on (optional)', 'type' => 'date', 'value' => $val('acquisition_date')]) ?>
            <?= $this->partial('partials/field', ['name' => 'acquisition_cost', 'label' => 'Acquisition cost (optional)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('acquisition_cost')]) ?>
        </div>
        <div class="field">
            <label for="f_notes">Notes</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('equipment')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add equipment' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
