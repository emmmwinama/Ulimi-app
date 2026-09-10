<?php
/**
 * @var array<string,mixed>|null $a
 * @var array<int,array<string,mixed>> $types @var array<int,array<string,mixed>> $parents
 * @var list<string> $sexes @var list<string> $statuses @var list<string> $acqTypes
 */
$this->layout('layouts/app');
$editing = $a !== null;
$action = $editing ? url('livestock/animals/' . rawurlencode((string) $a['id'])) : url('livestock/animals');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($a[$k] ?? $d);
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit animal' : 'Add animal' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body grid cols-2">
        <div class="field">
            <label for="f_type">Type <span aria-hidden="true" style="color:var(--red)">*</span></label>
            <select class="select" id="f_type" name="livestock_type_id" <?= $editing ? 'disabled' : 'required' ?>>
                <option value="">Select…</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= e((string) $t['id']) ?>" <?= (string) $val('livestock_type_id') === (string) $t['id'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($editing): ?><input type="hidden" name="livestock_type_id" value="<?= e((string) $val('livestock_type_id')) ?>"><?php endif; ?>
        </div>
        <div class="field">
            <label for="f_status">Status</label>
            <select class="select" id="f_status" name="status">
                <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= (string) ($val('status') ?: 'Active') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select>
        </div>

        <?= $this->partial('partials/field', ['name' => 'tag', 'label' => 'Tag / ID', 'value' => $val('tag')]) ?>
        <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'value' => $val('name')]) ?>

        <div class="field">
            <label for="f_sex">Sex</label>
            <select class="select" id="f_sex" name="sex">
                <?php foreach ($sexes as $s): ?><option value="<?= e($s) ?>" <?= (string) ($val('sex') ?: 'Unknown') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?= $this->partial('partials/field', ['name' => 'animal_group', 'label' => 'Group / pen', 'value' => $val('animal_group')]) ?>

        <?= $this->partial('partials/field', ['name' => 'breed', 'label' => 'Breed', 'value' => $val('breed')]) ?>
        <?= $this->partial('partials/field', ['name' => 'colour', 'label' => 'Colour', 'value' => $val('colour')]) ?>

        <?= $this->partial('partials/field', ['name' => 'birth_date', 'label' => 'Birth date', 'type' => 'date', 'value' => $val('birth_date')]) ?>
        <?= $this->partial('partials/field', ['name' => 'weight', 'label' => 'Current weight (kg)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('weight')]) ?>

        <?= $this->partial('partials/field', ['name' => 'acquisition_date', 'label' => 'Acquired on', 'type' => 'date', 'required' => true, 'value' => $val('acquisition_date', date('Y-m-d'))]) ?>
        <div class="field">
            <label for="f_acq">Acquisition</label>
            <select class="select" id="f_acq" name="acquisition_type">
                <?php foreach ($acqTypes as $t): ?><option value="<?= e($t) ?>" <?= (string) ($val('acquisition_type') ?: 'Born on farm') === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
            </select>
        </div>

        <?= $this->partial('partials/field', ['name' => 'acquisition_cost', 'label' => 'Acquisition cost', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('acquisition_cost')]) ?>
        <div class="field">
            <label for="f_parent">Parent (optional)</label>
            <select class="select" id="f_parent" name="parent_id">
                <option value="">—</option>
                <?php foreach ($parents as $p): if ($editing && $p['id'] === $a['id']) continue; ?>
                    <option value="<?= e((string) $p['id']) ?>" <?= (string) $val('parent_id') === (string) $p['id'] ? 'selected' : '' ?>>
                        <?= e(trim(((string) ($p['tag'] ?? '')) . ' ' . ((string) ($p['name'] ?? '')))) ?: '(untagged)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field" style="grid-column:1/-1">
            <label for="f_notes">Notes</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('livestock')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add animal' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
