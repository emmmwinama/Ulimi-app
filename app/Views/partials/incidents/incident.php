<?php
/**
 * @var array<string,mixed>|null $i
 * @var array<int,array<string,mixed>> $plantings
 * @var list<string> $types @var list<string> $severities @var list<string> $statuses
 * @var array<int,array<string,mixed>> $media
 */
$editing = $i !== null;
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($i[$k] ?? $d);
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$media = $media ?? [];
$uid = $editing ? (string) $i['id'] : 'new';
?>
<div class="field">
    <label for="f_crop_field_id_<?= e($uid) ?>">Crop planting</label>
    <select class="select" id="f_crop_field_id_<?= e($uid) ?>" name="crop_field_id" required>
        <option value="">Select a crop planting…</option>
        <?php foreach ($plantings as $p): ?>
            <option value="<?= e((string) $p['id']) ?>" <?= (string) $val('crop_field_id') === (string) $p['id'] ? 'selected' : '' ?>>
                <?= e((string) $p['crop_name']) ?> — <?= e((string) $p['field_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($er = error_for('crop_field_id')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
</div>

<div class="grid cols-2">
    <div class="field">
        <label for="f_type_<?= e($uid) ?>">Type</label>
        <select class="select" id="f_type_<?= e($uid) ?>" name="type">
            <?php foreach ($types as $t): ?>
                <option value="<?= e($t) ?>" <?= (string) ($val('type') ?: 'pest') === $t ? 'selected' : '' ?>><?= e($label($t)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="f_severity_<?= e($uid) ?>">Severity</label>
        <select class="select" id="f_severity_<?= e($uid) ?>" name="severity">
            <?php foreach ($severities as $s): ?>
                <option value="<?= e($s) ?>" <?= (string) ($val('severity') ?: 'moderate') === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?= $this->partial('partials/field', ['name' => 'reported_date', 'label' => 'Reported on', 'type' => 'date', 'required' => true, 'value' => $val('reported_date') ?: date('Y-m-d'), 'idSuffix' => $uid]) ?>

<div class="field">
    <label for="f_description_<?= e($uid) ?>">What did you see?</label>
    <textarea class="textarea" id="f_description_<?= e($uid) ?>" name="description" rows="3" required><?= e((string) $val('description')) ?></textarea>
    <?php if ($er = error_for('description')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
</div>

<?php if ($editing): ?>
    <div class="field">
        <label for="f_status_<?= e($uid) ?>">Status</label>
        <select class="select" id="f_status_<?= e($uid) ?>" name="status">
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>" <?= (string) ($val('status') ?: 'open') === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endif; ?>

<div class="field">
    <label for="f_treatment_notes_<?= e($uid) ?>">Treatment notes (optional)</label>
    <textarea class="textarea" id="f_treatment_notes_<?= e($uid) ?>" name="treatment_notes" rows="2"><?= e((string) $val('treatment_notes')) ?></textarea>
</div>

<?= $this->partial('partials/field', ['name' => 'referred_to', 'label' => 'Referred to a vet / extension officer (optional)', 'value' => $val('referred_to'), 'placeholder' => 'Name or contact', 'idSuffix' => $uid]) ?>

<div class="field">
    <label for="f_attachment_<?= e($uid) ?>">Add a photo or voice note (optional)</label>
    <input class="input" type="file" id="f_attachment_<?= e($uid) ?>" name="attachment" accept=".jpg,.jpeg,.png,.webp,.mp3,.m4a,.ogg"
           <?= error_for('attachment') ? 'aria-invalid="true"' : '' ?>>
    <p class="hint">JPG, PNG, WEBP, MP3, M4A or OGG, up to 8 MB. You can add more than one over time.</p>
    <?php if ($er = error_for('attachment')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
</div>

<?php if ($editing && $media !== []): ?>
    <div class="field">
        <label>Existing attachments</label>
        <div class="stack" style="--stack-gap:6px">
            <?php foreach ($media as $m): ?>
                <div class="row spread small">
                    <span><?= e((string) $m['name']) ?></span>
                    <a class="btn sm ghost" href="<?= e(url('documents/' . rawurlencode((string) $m['id']) . '/download')) ?>">Download</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
