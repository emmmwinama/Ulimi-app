<?php
/** @var array<int,array<string,mixed>> $tiers */
$this->layout('layouts/admin');
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div><h1 class="h1">Tiers</h1></div>
    <a class="btn" href="<?= e(url('admin/tiers/create')) ?>">Add tier</a>
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
                <a class="btn sm ghost" href="<?= e(url('admin/tiers/' . rawurlencode((string) $t['id']) . '/edit')) ?>">Edit</a>
                <form method="post" action="<?= e(url('admin/tiers/' . rawurlencode((string) $t['id']) . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this tier?')">
                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->stop(); ?>
