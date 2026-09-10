<?php
/**
 * Text-ish input field with label, hint, error and old-value repopulation.
 *
 * @var string  $name
 * @var string  $label
 * @var string  $type          text|email|password|number|tel|date  (default text)
 * @var ?string $value         override; defaults to old($name)
 * @var ?string $hint
 * @var bool    $required
 * @var ?string $autocomplete
 * @var ?string $placeholder
 * @var ?string $inputmode
 * @var ?string $step
 */
$type         = $type ?? 'text';
$required     = $required ?? false;
$value        = $value ?? old($name);
$err          = error_for($name);
$id           = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
$autocomplete = $autocomplete ?? null;
$placeholder  = $placeholder ?? null;
$inputmode    = $inputmode ?? null;
$step         = $step ?? null;
?>
<div class="field">
    <label for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' <span aria-hidden="true" style="color:var(--red)">*</span>' : '' ?></label>
    <input
        class="input"
        type="<?= e($type) ?>"
        id="<?= e($id) ?>"
        name="<?= e($name) ?>"
        value="<?= e(is_scalar($value) ? (string) $value : '') ?>"
        <?= $required ? 'required' : '' ?>
        <?= $autocomplete ? 'autocomplete="' . e($autocomplete) . '"' : '' ?>
        <?= $placeholder ? 'placeholder="' . e($placeholder) . '"' : '' ?>
        <?= $inputmode ? 'inputmode="' . e($inputmode) . '"' : '' ?>
        <?= $step ? 'step="' . e($step) . '"' : '' ?>
        <?= $type === 'email' ? 'spellcheck="false"' : '' ?>
        <?= $err ? 'aria-invalid="true" aria-describedby="' . e($id) . '_err"' : '' ?>
    >
    <?php if ($err): ?>
        <p class="err" id="<?= e($id) ?>_err"><?= e($err) ?></p>
    <?php elseif (!empty($hint)): ?>
        <p class="hint"><?= e($hint) ?></p>
    <?php endif; ?>
</div>
