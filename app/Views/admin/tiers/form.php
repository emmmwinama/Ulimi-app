<?php
/** @var array<string,mixed>|null $tier */
$this->layout('layouts/admin');
$editing = $tier !== null;
$action = $editing ? url('admin/tiers/' . rawurlencode((string) $tier['id'])) : url('admin/tiers');
$val = static fn (string $k, $d = '') => old($k) !== '' ? old($k) : ($tier[$k] ?? $d);
$limits = ['max_fields' => 'Fields', 'max_crops' => 'Crops', 'max_activities' => 'Activities', 'max_transactions' => 'Transactions', 'max_employees' => 'Employees', 'max_farms' => 'Farms', 'max_team_members' => 'Team members'];
$features = ['season_analytics' => 'Season analytics', 'yield_suggestions' => 'Yield suggestions', 'cost_per_hectare' => 'Cost per hectare', 'payroll_tracking' => 'Payroll tracking', 'multiple_farms' => 'Multiple farms', 'team_accounts' => 'Team accounts', 'custom_reports' => 'Custom reports', 'api_access' => 'API access', 'sync_enabled' => 'Cloud sync', 'data_retention_lifetime' => 'Lifetime data retention'];
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit tier' : 'Add tier' ?></h1></div></div>

<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <div class="grid cols-2">
            <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'required' => true, 'value' => $val('name')]) ?>
            <?= $this->partial('partials/field', ['name' => 'currency', 'label' => 'Currency', 'required' => true, 'value' => $val('currency', 'MWK')]) ?>
        </div>
        <div class="field"><label class="small">Description</label><textarea class="textarea" name="description" rows="2"><?= e((string) $val('description')) ?></textarea></div>
        <div class="grid cols-3">
            <?= $this->partial('partials/field', ['name' => 'price_monthly', 'label' => 'Price / month', 'type' => 'number', 'step' => 'any', 'required' => true, 'value' => $val('price_monthly', 0)]) ?>
            <?= $this->partial('partials/field', ['name' => 'price_annual', 'label' => 'Price / year', 'type' => 'number', 'step' => 'any', 'value' => $val('price_annual')]) ?>
            <?= $this->partial('partials/field', ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'value' => $val('sort_order', 0)]) ?>
        </div>
        <div class="row wrap" style="gap:16px">
            <label class="checkline"><input type="checkbox" name="is_active" value="1" <?= (int) $val('is_active', 1) === 1 ? 'checked' : '' ?>> Active</label>
            <label class="checkline"><input type="checkbox" name="is_public" value="1" <?= (int) $val('is_public', 1) === 1 ? 'checked' : '' ?>> Public</label>
            <label class="checkline"><input type="checkbox" name="is_featured" value="1" <?= (int) $val('is_featured', 0) === 1 ? 'checked' : '' ?>> Featured</label>
        </div>

        <div class="h3 mt-16px">Limits (leave blank for unlimited)</div>
        <div class="grid cols-4">
            <?php foreach ($limits as $key => $label): ?>
                <div class="field">
                    <label class="small"><?= e($label) ?></label>
                    <input class="input" type="number" name="<?= e($key) ?>" value="<?= e((string) $val($key, 1)) ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="h3 mt-16px">Features</div>
        <div class="grid cols-3">
            <?php foreach ($features as $key => $label): ?>
                <label class="checkline"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= (int) $val($key, 0) === 1 ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('admin/tiers')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Create tier' ?></button>
    </div>
</form>
<?php $this->stop(); ?>
