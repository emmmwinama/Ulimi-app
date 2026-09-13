<?php
/** @var array<int,array<string,mixed>> $tiers */
$this->layout('layouts/admin');
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div><h1 class="h1">Tiers</h1></div>
    <a class="btn" href="#add-tier"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Add tier</a>
</div>

<div class="table-wrap"><table class="data">
    <thead><tr><th>Name</th><th class="num">Price/mo</th><th>Active</th><th>Public</th><th class="num">Max fields</th><th class="num">Max farms</th><th class="num">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($tiers as $t): ?>
        <tr>
            <td><strong><?= e((string) $t['name']) ?></strong><?php if ((int) $t['is_featured'] === 1): ?> <span class="badge blue">Featured</span><?php endif; ?></td>
            <td class="num"><?= e(Money::format((float) $t['price_monthly'], (string) $t['currency'])) ?></td>
            <td><span class="badge <?= (int) $t['is_active'] === 1 ? 'green' : '' ?>"><?= (int) $t['is_active'] === 1 ? 'Yes' : 'No' ?></span></td>
            <td><span class="badge <?= (int) $t['is_public'] === 1 ? 'green' : '' ?>"><?= (int) $t['is_public'] === 1 ? 'Yes' : 'No' ?></span></td>
            <td class="num"><?= (int) $t['max_fields'] === -1 ? '∞' : e((string) $t['max_fields']) ?></td>
            <td class="num"><?= (int) $t['max_farms'] === -1 ? '∞' : e((string) $t['max_farms']) ?></td>
            <td class="num nowrap">
                <a class="btn sm ghost" href="#edit-tier-<?= e((string) $t['id']) ?>">Edit</a>
                <form method="post" action="<?= e(url('admin/tiers/' . rawurlencode((string) $t['id']) . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this tier?')">
                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>

<div class="slide-over" id="add-tier">
    <a href="#" class="scrim" aria-label="Close"></a>
    <div class="panel wide">
        <div class="panel-head">
            <div>
                <h2 class="h3">Add tier</h2>
                <p class="small muted mt-8px">Create a new subscription plan</p>
            </div>
            <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
        </div>
        <form method="post" action="<?= e(url('admin/tiers')) ?>" style="display:contents">
            <?= csrf_field() ?>
            <div class="panel-body stack">
                <?= $this->partial('partials/admin/tier-fields', ['tier' => null]) ?>
            </div>
            <div class="panel-foot">
                <a href="#" class="btn ghost block">Cancel</a>
                <button type="submit" class="btn block">Create tier</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($tiers as $t): ?>
    <div class="slide-over" id="edit-tier-<?= e((string) $t['id']) ?>">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel wide">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Edit tier</h2>
                    <p class="small muted mt-8px"><?= e((string) $t['name']) ?></p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('admin/tiers/' . rawurlencode((string) $t['id']))) ?>" style="display:contents">
                <?= csrf_field() ?>
                <?= method_field('PUT') ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/admin/tier-fields', ['tier' => $t]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Save changes</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php $this->stop(); ?>
