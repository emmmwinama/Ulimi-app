<?php
/**
 * Shared field-set for the Add/Edit transaction form — included by both the
 * standalone form page and the index page's slide-over panels so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $row
 * @var array<int,array<string,mixed>> $fields
 * @var array<int,array<string,mixed>> $crops
 * @var list<string> $incomeCats
 * @var list<string> $expenseCats
 */
$editing = $row !== null;
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($row[$k] ?? $d);
$allCats = array_values(array_unique([...$incomeCats, ...$expenseCats]));
$uid = $editing ? (string) $row['id'] : 'new';
?>
<div class="grid cols-2">
    <div class="field">
        <label for="f_type_<?= e($uid) ?>">Type <span aria-hidden="true" style="color:var(--red)">*</span></label>
        <select class="select" id="f_type_<?= e($uid) ?>" name="type">
            <option value="Income" <?= (string) ($val('type') ?: 'Expense') === 'Income' ? 'selected' : '' ?>>Income</option>
            <option value="Expense" <?= (string) ($val('type') ?: 'Expense') === 'Expense' ? 'selected' : '' ?>>Expense</option>
        </select>
    </div>
    <div class="field">
        <label for="f_category_<?= e($uid) ?>">Category <span aria-hidden="true" style="color:var(--red)">*</span></label>
        <input class="input" list="cat_list_<?= e($uid) ?>" id="f_category_<?= e($uid) ?>" name="category" required value="<?= e((string) $val('category')) ?>"
               <?= error_for('category') ? 'aria-invalid="true"' : '' ?>>
        <datalist id="cat_list_<?= e($uid) ?>"><?php foreach ($allCats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
        <?php if ($er = error_for('category')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
    </div>
</div>

<div class="grid cols-2">
    <?= $this->partial('partials/field', [
        'name' => 'amount', 'label' => 'Amount (' . e((string) config('app.currency')) . ')', 'type' => 'number',
        'step' => 'any', 'inputmode' => 'decimal', 'required' => true, 'value' => $val('amount'), 'idSuffix' => $uid,
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true, 'value' => $val('date', date('Y-m-d')), 'idSuffix' => $uid,
    ]) ?>
</div>

<?= $this->partial('partials/field', [
    'name' => 'description', 'label' => 'Description', 'required' => true, 'value' => $val('description'), 'idSuffix' => $uid,
]) ?>

<div class="grid cols-2">
    <div class="field">
        <label for="f_field_id_<?= e($uid) ?>">Field (optional)</label>
        <select class="select" id="f_field_id_<?= e($uid) ?>" name="field_id">
            <option value="">—</option>
            <?php foreach ($fields as $f): ?>
                <option value="<?= e((string) $f['id']) ?>" <?= (string) $val('field_id') === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f_crop_field_id_<?= e($uid) ?>">Crop planting (optional)</label>
        <select class="select" id="f_crop_field_id_<?= e($uid) ?>" name="crop_field_id">
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
    'name' => 'season', 'label' => 'Season (optional)', 'value' => $val('season'), 'idSuffix' => $uid,
    'hint' => 'Tag the transaction to a season for season-level margins.',
]) ?>
