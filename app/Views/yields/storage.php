<?php
/**
 * @var array<string,mixed> $y harvest yield row (with crop_name/field_name)
 * @var array<string,mixed>|null $storage
 * @var list<string> $grades
 */
$this->layout('layouts/app');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($storage[$k] ?? $d);
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Storage — <?= e((string) $y['crop_name']) ?></h1>
        <p class="lede"><?= e((string) $y['field_name']) ?> · harvested <?= e((string) $y['harvest_date']) ?></p>
    </div>
</div>

<div class="content-narrow">
<form method="post" action="<?= e(url('yields/' . rawurlencode((string) $y['id']) . '/storage')) ?>" class="card">
    <?= csrf_field() ?>
    <div class="card-body stack">
        <?= $this->partial('partials/field', ['name' => 'storage_location', 'label' => 'Storage location (optional)', 'value' => $val('storage_location'), 'placeholder' => 'e.g. Shed 2, rented store in town']) ?>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'drying_method', 'label' => 'Drying method (optional)', 'value' => $val('drying_method'), 'placeholder' => 'Sun-dried, crib, tarpaulin']) ?>
            <?= $this->partial('partials/field', ['name' => 'drying_date', 'label' => 'Drying date (optional)', 'type' => 'date', 'value' => $val('drying_date')]) ?>
        </div>
        <div class="field">
            <label for="f_quality_grade">Quality grade (optional)</label>
            <select class="select" id="f_quality_grade" name="quality_grade">
                <option value="">Not graded</option>
                <?php foreach ($grades as $g): ?>
                    <option value="<?= e($g) ?>" <?= (string) $val('quality_grade') === $g ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'expected_loss_qty', 'label' => 'Expected loss (kg, optional)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('expected_loss_qty')]) ?>
            <?= $this->partial('partials/field', ['name' => 'loss_reason', 'label' => 'Loss reason (optional)', 'value' => $val('loss_reason'), 'placeholder' => 'Pests, mould, spillage']) ?>
        </div>
        <div class="field">
            <label for="f_notes">Notes</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('yields')) ?>">Back to yields</a>
        <button type="submit" class="btn">Save</button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
