<?php
/**
 * Shared body for the Log/Edit activity form — included by both the
 * standalone form page and the index page's slide-over panel so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $a       activity with labour/inputs/other, or null
 * @var array<int,array<string,mixed>> $fields
 * @var array<int,array<string,mixed>> $crops
 * @var array<int,array<string,mixed>> $employees
 * @var list<string> $types
 * @var bool $payroll
 */
$editing = $a !== null;
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($a[$k] ?? $d);

$labourRows = $editing ? ($a['labour'] ?: [[]]) : [[]];
$inputRows  = $editing ? ($a['inputs'] ?: [[]]) : [[]];
$otherRows  = $editing ? ($a['other'] ?: [[]]) : [[]];
?>
<div class="card">
    <div class="card-body grid cols-2">
        <div class="field">
            <label for="f_field_id">Field <span aria-hidden="true" style="color:var(--red)">*</span></label>
            <select class="select" id="f_field_id" name="field_id" required <?= error_for('field_id') ? 'aria-invalid="true"' : '' ?>>
                <option value="">Select…</option>
                <?php foreach ($fields as $f): ?>
                    <option value="<?= e((string) $f['id']) ?>" <?= (string) $val('field_id') === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($er = error_for('field_id')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="f_crop_field_id">Crop planting (optional)</label>
            <select class="select" id="f_crop_field_id" name="crop_field_id">
                <option value="">— none —</option>
                <?php foreach ($crops as $c): ?>
                    <option value="<?= e((string) $c['id']) ?>"
                            data-types="<?= e(implode('|', \App\Services\CropTimeline::activityTypes((string) $c['crop_name']))) ?>"
                            <?= (string) $val('crop_field_id') === (string) $c['id'] ? 'selected' : '' ?>>
                        <?= e((string) $c['crop_name']) ?> — <?= e((string) $c['field_name']) ?> (<?= e((string) $c['season']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="hint" data-type-hint></p>
        </div>

        <div class="field">
            <label for="f_activity_type">Activity type <span aria-hidden="true" style="color:var(--red)">*</span></label>
            <select class="select" id="f_activity_type" name="activity_type" required <?= error_for('activity_type') ? 'aria-invalid="true"' : '' ?>>
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= (string) $val('activity_type') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($er = error_for('activity_type')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
        </div>

        <?= $this->partial('partials/field', [
            'name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true,
            'value' => $val('date', date('Y-m-d')),
        ]) ?>

        <div class="field">
            <label for="f_responsible_employee_id">Responsible (roster)</label>
            <select class="select" id="f_responsible_employee_id" name="responsible_employee_id">
                <option value="">— not from roster —</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= e((string) $emp['id']) ?>" <?= (string) $val('responsible_employee_id') === (string) $emp['id'] ? 'selected' : '' ?>><?= e((string) $emp['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?= $this->partial('partials/field', [
            'name' => 'responsible', 'label' => 'Responsible (name)',
            'value' => $val('responsible_person_name'), 'placeholder' => 'If not on the roster',
        ]) ?>

        <div class="field" style="grid-column:1/-1">
            <label for="f_notes">Notes</label>
            <textarea class="textarea" id="f_notes" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
        </div>
    </div>
</div>

<?php /* ---- Labour ---- */ ?>
<div class="card" data-rowset="labour">
    <div class="card-head">
        <h2 class="h2">Labour</h2>
        <button type="button" class="btn secondary sm" data-add-row>+ Add row</button>
    </div>
    <div class="card-body table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>Employee</th><th>Or casual name</th><th class="num">Hours</th><th class="num">Days</th><th class="num">Cost (<?= e((string) config('app.currency')) ?>)</th><th></th></tr></thead>
            <tbody data-rows>
            <?php foreach ($labourRows as $r): ?>
                <tr data-row>
                    <td>
                        <select class="select" name="labour_employee[]">
                            <option value="">—</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= e((string) $emp['id']) ?>" <?= ($r['employee_id'] ?? '') === $emp['id'] ? 'selected' : '' ?>><?= e((string) $emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input class="input" name="labour_worker[]" value="<?= e((string) ($r['worker_name'] ?? '')) ?>" placeholder="e.g. 4 casuals"></td>
                    <td><input class="input" type="number" step="any" name="labour_hours[]" value="<?= e((string) ($r['hours_worked'] ?? '')) ?>" style="max-width:90px"></td>
                    <td><input class="input" type="number" step="any" name="labour_days[]" value="<?= e((string) ($r['days_worked'] ?? '')) ?>" style="max-width:90px"></td>
                    <td><input class="input" type="number" step="any" name="labour_cost[]" value="<?= e((string) ($r['total_cost'] ?? '')) ?>" style="max-width:120px"></td>
                    <td><button type="button" class="btn ghost sm" data-remove-row>✕</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$payroll): ?><p class="hint">Payroll costing is a paid feature — labour rows are recorded but not costed automatically.</p><?php endif; ?>
    </div>
</div>

<?php /* ---- Inputs ---- */ ?>
<div class="card" data-rowset="inputs">
    <div class="card-head">
        <h2 class="h2">Inputs</h2>
        <button type="button" class="btn secondary sm" data-add-row>+ Add row</button>
    </div>
    <div class="card-body table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>Input</th><th>Category</th><th class="num">Qty</th><th>Unit</th><th class="num">Unit cost</th><th></th></tr></thead>
            <tbody data-rows>
            <?php foreach ($inputRows as $r): ?>
                <tr data-row>
                    <td><input class="input" name="input_name[]" value="<?= e((string) ($r['input_name'] ?? '')) ?>" placeholder="e.g. Urea"></td>
                    <td>
                        <input class="input" list="input_cats" name="input_category[]" value="<?= e((string) ($r['category'] ?? '')) ?>" style="max-width:150px">
                    </td>
                    <td><input class="input" type="number" step="any" name="input_qty[]" value="<?= e((string) ($r['quantity'] ?? '')) ?>" style="max-width:90px"></td>
                    <td><input class="input" name="input_unit[]" value="<?= e((string) ($r['unit'] ?? '')) ?>" style="max-width:80px" placeholder="kg"></td>
                    <td><input class="input" type="number" step="any" name="input_unitcost[]" value="<?= e((string) ($r['unit_cost'] ?? '')) ?>" style="max-width:120px"></td>
                    <td><button type="button" class="btn ghost sm" data-remove-row>✕</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <datalist id="input_cats">
            <option value="Seed"><option value="Fertiliser"><option value="Chemical"><option value="Fuel"><option value="Other">
        </datalist>
    </div>
</div>

<?php /* ---- Other costs ---- */ ?>
<div class="card" data-rowset="other">
    <div class="card-head">
        <h2 class="h2">Other costs</h2>
        <button type="button" class="btn secondary sm" data-add-row>+ Add row</button>
    </div>
    <div class="card-body table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>Description</th><th class="num">Amount</th><th></th></tr></thead>
            <tbody data-rows>
            <?php foreach ($otherRows as $r): ?>
                <tr data-row>
                    <td><input class="input" name="other_desc[]" value="<?= e((string) ($r['description'] ?? '')) ?>" placeholder="e.g. Tractor hire"></td>
                    <td><input class="input" type="number" step="any" name="other_amount[]" value="<?= e((string) ($r['amount'] ?? '')) ?>" style="max-width:140px"></td>
                    <td><button type="button" class="btn ghost sm" data-remove-row>✕</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
