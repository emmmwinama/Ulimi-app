<?php
/**
 * @var array<string,mixed>|null $e
 * @var list<string> $types
 * @var array<int,array<string,mixed>> $plantings
 * @var array<int,array<string,mixed>> $media
 */
$this->layout('layouts/app');
$editing = $e !== null;
$action = $editing ? url('climate-events/' . rawurlencode((string) $e['id'])) : url('climate-events');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($e[$k] ?? $d);
$label = static fn (string $s): string => ucfirst($s);
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit climate event' : 'Record a climate event' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <div class="field">
            <label for="f_event_type">Type</label>
            <select class="select" id="f_event_type" name="event_type">
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= (string) ($val('event_type') ?: 'drought') === $t ? 'selected' : '' ?>><?= e($label($t)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'start_date', 'label' => 'Start date', 'type' => 'date', 'required' => true, 'value' => $val('start_date') ?: date('Y-m-d')]) ?>
            <?= $this->partial('partials/field', ['name' => 'end_date', 'label' => 'End date (optional)', 'type' => 'date', 'value' => $val('end_date')]) ?>
        </div>
        <div class="field">
            <label for="f_affected_crop_field_id">Affected crop planting (optional)</label>
            <select class="select" id="f_affected_crop_field_id" name="affected_crop_field_id">
                <option value="">— whole farm / not specific —</option>
                <?php foreach ($plantings as $p): ?>
                    <option value="<?= e((string) $p['id']) ?>" <?= (string) $val('affected_crop_field_id') === (string) $p['id'] ? 'selected' : '' ?>>
                        <?= e((string) $p['crop_name']) ?> — <?= e((string) $p['field_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="f_description">What happened (optional)</label>
            <textarea class="textarea" id="f_description" name="description" rows="3"><?= e((string) $val('description')) ?></textarea>
        </div>
        <?= $this->partial('partials/field', ['name' => 'estimated_loss_amount', 'label' => 'Estimated loss (optional)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'value' => $val('estimated_loss_amount')]) ?>

        <div class="field">
            <label for="f_evidence">Evidence photo (optional)</label>
            <input class="input" type="file" id="f_evidence" name="evidence" accept=".jpg,.jpeg,.png,.webp"
                   <?= error_for('evidence') ? 'aria-invalid="true"' : '' ?>>
            <p class="hint">JPG, PNG or WEBP, up to 8 MB.</p>
        </div>

        <?php if ($editing && $media !== []): ?>
            <div class="field">
                <label>Existing evidence</label>
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
        <a class="btn ghost" href="<?= e(url('climate-events')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Record event' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
