<?php
/**
 * @var array<string,mixed>|null $row
 * @var array<int,array<string,mixed>> $fields @var array<int,array<string,mixed>> $crops
 * @var list<string> $incomeCats @var list<string> $expenseCats
 */
$this->layout('layouts/app');
$editing = $row !== null;
$action = $editing ? url('finance/transactions/' . rawurlencode((string) $row['id'])) : url('finance/transactions');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($row[$k] ?? $d);
$allCats = array_values(array_unique([...$incomeCats, ...$expenseCats]));
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit transaction' : 'Add transaction' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <div class="grid cols-2">
            <div class="field">
                <label for="f_type">Type <span aria-hidden="true" style="color:var(--red)">*</span></label>
                <select class="select" id="f_type" name="type">
                    <option value="Income" <?= (string) ($val('type') ?: 'Expense') === 'Income' ? 'selected' : '' ?>>Income</option>
                    <option value="Expense" <?= (string) ($val('type') ?: 'Expense') === 'Expense' ? 'selected' : '' ?>>Expense</option>
                </select>
            </div>
            <div class="field">
                <label for="f_category">Category <span aria-hidden="true" style="color:var(--red)">*</span></label>
                <input class="input" list="cat_list" id="f_category" name="category" required value="<?= e((string) $val('category')) ?>"
                       <?= error_for('category') ? 'aria-invalid="true"' : '' ?>>
                <datalist id="cat_list"><?php foreach ($allCats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
                <?php if ($er = error_for('category')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
            </div>
        </div>

        <div class="grid cols-2">
            <?= $this->partial('partials/field', [
                'name' => 'amount', 'label' => 'Amount (' . e((string) config('app.currency')) . ')', 'type' => 'number',
                'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('amount'),
            ]) ?>
            <?= $this->partial('partials/field', [
                'name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true, 'value' => $val('date', date('Y-m-d')),
            ]) ?>
        </div>

        <?= $this->partial('partials/field', [
            'name' => 'description', 'label' => 'Description', 'required' => true, 'value' => $val('description'),
        ]) ?>

        <div class="grid cols-2">
            <div class="field">
                <label for="f_field_id">Field (optional)</label>
                <select class="select" id="f_field_id" name="field_id">
                    <option value="">—</option>
                    <?php foreach ($fields as $f): ?>
                        <option value="<?= e((string) $f['id']) ?>" <?= (string) $val('field_id') === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f_crop_field_id">Crop planting (optional)</label>
                <select class="select" id="f_crop_field_id" name="crop_field_id">
                    <option value="">—</option>
                    <?php foreach ($crops as $c): ?>
                        <option value="<?= e((string) $c['id']) ?>" data-season="<?= e((string) $c['season']) ?>" <?= (string) $val('crop_field_id') === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= e((string) $c['crop_name']) ?> — <?= e((string) $c['field_name']) ?> (<?= e((string) $c['season']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?= $this->partial('partials/field', [
            'name' => 'season', 'label' => 'Season (optional)', 'value' => $val('season'),
            'hint' => 'Tag the transaction to a season for season-level margins.',
        ]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('finance/transactions')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add transaction' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
