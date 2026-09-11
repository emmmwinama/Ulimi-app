<?php
/**
 * @var array<string,mixed> $farm
 * @var bool $isOwner
 */
$this->layout('layouts/app');
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Farm settings</h1>
        <p class="lede">Profile details for <?= e((string) $farm['name']) ?></p>
    </div>
</div>

<div class="content-narrow stack" style="--stack-gap:24px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Profile</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('settings')) ?>" class="stack">
                <?= csrf_field() ?>
                <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Farm name', 'required' => true, 'value' => old('name') ?: (string) $farm['name']]) ?>
                <?= $this->partial('partials/field', ['name' => 'location', 'label' => 'Location / district', 'required' => true, 'value' => old('location') ?: (string) $farm['location']]) ?>
                <?= $this->partial('partials/field', ['name' => 'owner_name', 'label' => 'Owner name (optional)', 'value' => old('owner_name') ?: (string) ($farm['owner_name'] ?? '')]) ?>
                <div class="grid cols-2">
                    <?= $this->partial('partials/field', ['name' => 'location_lat', 'label' => 'Latitude', 'type' => 'number', 'step' => 'any', 'value' => old('location_lat') ?: (string) ($farm['location_lat'] ?? '')]) ?>
                    <?= $this->partial('partials/field', ['name' => 'location_lng', 'label' => 'Longitude', 'type' => 'number', 'step' => 'any', 'value' => old('location_lng') ?: (string) ($farm['location_lng'] ?? '')]) ?>
                </div>
                <?php if (!$isOwner): ?><p class="hint">Only the farm owner can change these settings.</p><?php endif; ?>
                <div><button type="submit" class="btn" <?= $isOwner ? '' : 'disabled' ?>>Save changes</button></div>
            </form>
        </div>
    </div>

    <?php if ($isOwner): ?>
    <div class="card" style="border-color:#FECACA">
        <div class="card-head"><h2 class="h2" style="color:var(--red)">Danger zone</h2></div>
        <div class="card-body">
            <p class="small muted mb-16px">Deleting this farm permanently removes every field, crop, activity, transaction, livestock and document record. This cannot be undone.</p>
            <details>
                <summary class="btn danger sm" style="display:inline-flex;cursor:pointer;list-style:none">Delete this farm</summary>
                <form method="post" action="<?= e(url('settings/delete')) ?>" class="mt-16px stack" style="max-width:360px" onsubmit="return confirm('This is permanent. Continue?')">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="f_confirm">Type <strong><?= e((string) $farm['name']) ?></strong> to confirm</label>
                        <input class="input" id="f_confirm" name="confirm_name" required autocomplete="off"
                               <?= error_for('confirm_name') ? 'aria-invalid="true"' : '' ?>>
                        <?php if ($er = error_for('confirm_name')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
                    </div>
                    <button type="submit" class="btn danger">Permanently delete farm</button>
                </form>
            </details>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $this->stop(); ?>
