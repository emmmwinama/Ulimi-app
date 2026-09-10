<?php
/**
 * @var array<int,array<string,mixed>> $items
 * @var list<string> $categories @var string $category
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Money;
$label = static fn (string $c): string => ucwords(str_replace('_', ' ', $c));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Inventory</h1>
        <p class="lede"><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> in stock</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('inventory/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add stock item
        </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('inventory')) ?>" class="mb-16">
    <select class="select" name="category" onchange="this.form.submit()" style="max-width:220px">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e($label($c)) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($items === []): ?>
    <div class="empty">
        <div class="h3">No stock recorded</div>
        <p>Track harvested produce and input stock (seed, fertiliser, chemicals). Sales reduce stock and post to Finance automatically.</p>
        <?php if ($canManage): ?><p class="mt-16"><a class="btn" href="<?= e(url('inventory/create')) ?>">Add a stock item</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Item</th><th>Category</th><th class="num">In stock</th><th class="num">Acq. cost</th><th>Season</th><?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <?php $stock = (float) $it['quantity']; ?>
            <tr>
                <td><strong><?= e((string) $it['name']) ?></strong></td>
                <td class="small"><?= e($label((string) $it['category'])) ?></td>
                <td class="num <?= $stock <= 0 ? 'muted' : '' ?>"><?= e(rtrim(rtrim(number_format($stock, 3), '0'), '.')) ?> <?= e((string) $it['unit']) ?></td>
                <td class="num"><?= $it['acquisition_unit_cost'] !== null ? e(Money::format((float) $it['acquisition_unit_cost'])) : '—' ?></td>
                <td class="small muted"><?= e((string) ($it['season'] ?: '—')) ?></td>
                <?php if ($canManage): ?>
                    <td class="num nowrap">
                        <a class="btn sm" href="<?= e(url('inventory/' . rawurlencode((string) $it['id']) . '/sell')) ?>">Sell</a>
                        <a class="btn sm ghost" href="<?= e(url('inventory/' . rawurlencode((string) $it['id']) . '/edit')) ?>">Edit</a>
                        <form method="post" action="<?= e(url('inventory/' . rawurlencode((string) $it['id']) . '/delete')) ?>"
                              style="display:inline" onsubmit="return confirm('Delete this stock item?')">
                            <?= csrf_field() ?>
                            <button class="btn sm ghost danger" type="submit">Delete</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php $this->stop(); ?>
