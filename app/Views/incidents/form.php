<?php
/**
 * @var array<string,mixed>|null $i
 * @var array<int,array<string,mixed>> $plantings
 * @var list<string> $types @var list<string> $severities @var list<string> $statuses
 * @var array<int,array<string,mixed>> $media
 */
$this->layout('layouts/app');
$editing = $i !== null;
$action = $editing ? url('incidents/' . rawurlencode((string) $i['id'])) : url('incidents');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($i[$k] ?? $d);
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit incident' : 'Report an incident' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <div class="field">
            <label for="f_crop_field_id">Crop planting</label>
            <select class="select" id="f_crop_field_id" name="crop_field_id" required>
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
                <label for="f_type">Type</label>
                <select class="select" id="f_type" name="type">
                    <?php foreach ($types as $t): ?>
                        <option value="<?= e($t) ?>" <?= (string) ($val('type') ?: 'pest') === $t ? 'selected' : '' ?>><?= e($label($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="f_severity">Severity</label>
                <select class="select" id="f_severity" name="severity">
                    <?php foreach ($severities as $s): ?>
                        <option value="<?= e($s) ?>" <?= (string) ($val('severity') ?: 'moderate') === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?= $this->partial('partials/field', ['name' => 'reported_date', 'label' => 'Reported on', 'type' => 'date', 'required' => true, 'value' => $val('reported_date') ?: date('Y-m-d')]) ?>

        <div class="field">
            <label for="f_description">What did you see?</label>
            <textarea class="textarea" id="f_description" name="description" rows="3" required><?= e((string) $val('description')) ?></textarea>
            <?php if ($er = error_for('description')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
        </div>

        <?php if ($editing): ?>
            <div class="field">
                <label for="f_status">Status</label>
                <select class="select" id="f_status" name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= (string) ($val('status') ?: 'open') === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="field">
            <label for="f_treatment_notes">Treatment notes (optional)</label>
            <textarea class="textarea" id="f_treatment_notes" name="treatment_notes" rows="2"><?= e((string) $val('treatment_notes')) ?></textarea>
        </div>

        <?= $this->partial('partials/field', ['name' => 'referred_to', 'label' => 'Referred to a vet / extension officer (optional)', 'value' => $val('referred_to'), 'placeholder' => 'Name or contact']) ?>

        <div class="field">
            <label for="f_attachment">Add a photo or voice note (optional)</label>
            <input class="input" type="file" id="f_attachment" name="attachment" accept=".jpg,.jpeg,.png,.webp,.mp3,.m4a,.ogg"
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
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('incidents')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Report incident' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
