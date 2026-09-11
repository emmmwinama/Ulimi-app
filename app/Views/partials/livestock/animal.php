<?php
/**
 * Shared field-set for the Add/Edit animal form — included by both the
 * standalone form page and the index page's slide-over panel so the two
 * never drift apart.
 *
 * @var array<string,mixed>|null $a
 * @var array<int,array<string,mixed>> $types
 * @var array<int,array<string,mixed>> $parents
 * @var list<string> $sexes
 * @var list<string> $statuses
 * @var list<string> $acqTypes
 */
$editing = $a !== null;
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($a[$k] ?? $d);
$uid = $editing ? (string) $a['id'] : 'new';
?>
<div class="grid cols-2">
    <div class="field">
        <label for="f_type_<?= e($uid) ?>">Type <span aria-hidden="true" style="color:var(--red)">*</span></label>
        <select class="select" id="f_type_<?= e($uid) ?>" name="livestock_type_id" <?= $editing ? 'disabled' : 'required' ?>>
            <option value="">Select…</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= e((string) $t['id']) ?>" <?= (string) $val('livestock_type_id') === (string) $t['id'] ? 'selected' : '' ?>><?= e((string) $t['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($editing): ?><input type="hidden" name="livestock_type_id" value="<?= e((string) $val('livestock_type_id')) ?>"><?php endif; ?>
    </div>
    <div class="field">
        <label for="f_status_<?= e($uid) ?>">Status</label>
        <select class="select" id="f_status_<?= e($uid) ?>" name="status">
            <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>" <?= (string) ($val('status') ?: 'Active') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
    </div>

    <?= $this->partial('partials/field', ['name' => 'tag', 'label' => 'Tag / ID', 'value' => $val('tag'), 'idSuffix' => $uid]) ?>
    <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'value' => $val('name'), 'idSuffix' => $uid]) ?>

    <div class="field">
        <label for="f_sex_<?= e($uid) ?>">Sex</label>
        <select class="select" id="f_sex_<?= e($uid) ?>" name="sex">
            <?php foreach ($sexes as $s): ?><option value="<?= e($s) ?>" <?= (string) ($val('sex') ?: 'Unknown') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
    </div>
    <?= $this->partial('partials/field', ['name' => 'animal_group', 'label' => 'Group / pen', 'value' => $val('animal_group'), 'idSuffix' => $uid]) ?>

    <?= $this->partial('partials/field', ['name' => 'breed', 'label' => 'Breed', 'value' => $val('breed'), 'idSuffix' => $uid]) ?>
    <?= $this->partial('partials/field', ['name' => 'colour', 'label' => 'Colour', 'value' => $val('colour'), 'idSuffix' => $uid]) ?>

    <?= $this->partial('partials/field', ['name' => 'birth_date', 'label' => 'Birth date', 'type' => 'date', 'value' => $val('birth_date'), 'idSuffix' => $uid]) ?>
    <?= $this->partial('partials/field', ['name' => 'weight', 'label' => 'Current weight (kg)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('weight'), 'idSuffix' => $uid]) ?>

    <?= $this->partial('partials/field', ['name' => 'acquisition_date', 'label' => 'Acquired on', 'type' => 'date', 'required' => true, 'value' => $val('acquisition_date', date('Y-m-d')), 'idSuffix' => $uid]) ?>
    <div class="field">
        <label for="f_acq_<?= e($uid) ?>">Acquisition</label>
        <select class="select" id="f_acq_<?= e($uid) ?>" name="acquisition_type">
            <?php foreach ($acqTypes as $t): ?><option value="<?= e($t) ?>" <?= (string) ($val('acquisition_type') ?: 'Born on farm') === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
    </div>

    <?= $this->partial('partials/field', ['name' => 'acquisition_cost', 'label' => 'Acquisition cost', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('acquisition_cost'), 'idSuffix' => $uid]) ?>
    <div class="field">
        <label for="f_parent_<?= e($uid) ?>">Parent (optional)</label>
        <select class="select" id="f_parent_<?= e($uid) ?>" name="parent_id">
            <option value="">—</option>
            <?php foreach ($parents as $p): if ($editing && $p['id'] === $a['id']) continue; ?>
                <option value="<?= e((string) $p['id']) ?>" <?= (string) $val('parent_id') === (string) $p['id'] ? 'selected' : '' ?>>
                    <?= e(trim(((string) ($p['tag'] ?? '')) . ' ' . ((string) ($p['name'] ?? '')))) ?: '(untagged)' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field" style="grid-column:1/-1">
        <label for="f_notes_<?= e($uid) ?>">Notes</label>
        <textarea class="textarea" id="f_notes_<?= e($uid) ?>" name="notes" rows="2"><?= e((string) $val('notes')) ?></textarea>
    </div>
</div>
