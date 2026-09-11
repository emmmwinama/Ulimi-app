<?php
/**
 * @var array<int,array<string,mixed>> $items
 * @var list<string> $categories @var string $category
 * @var bool $canManage
 * @var array<int,array{category:string,count:int,total_revenue:float}> $byCategory
 * @var float $totalRevenue
 */
$this->layout('layouts/app');
use App\Support\Money;
$label = static fn (string $c): string => ucwords(str_replace('_', ' ', $c));
$catIcon = [
    'crop_harvest' => 'wheat', 'seed' => 'sprout', 'fertiliser' => 'leaf',
    'chemical' => 'alert', 'equipment' => 'settings', 'other' => 'boxes',
];
$catColor = [
    'crop_harvest' => ['bg' => 'var(--green-050)', 'fg' => 'var(--green-text)'],
    'seed'         => ['bg' => '#F0F9FF', 'fg' => '#075985'],
    'fertiliser'   => ['bg' => 'var(--blue-050)', 'fg' => '#1E3A8A'],
    'chemical'     => ['bg' => 'var(--amber-050)', 'fg' => 'var(--amber)'],
    'equipment'    => ['bg' => 'var(--surface-2)', 'fg' => 'var(--text-soft)'],
    'other'        => ['bg' => '#F5F3FF', 'fg' => '#3C3489'],
];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Inventory</h1>
        <p class="lede"><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?> · <?= e(Money::format($totalRevenue)) ?> in sales</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('inventory/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add stock item
        </a>
    <?php endif; ?>
</div>

<?php if ($byCategory !== []): ?>
    <div class="grid cols-4 mb-16px">
        <?php foreach (array_slice($byCategory, 0, 4) as $c): ?>
            <div class="stat">
                <div class="label"><?= e($label($c['category'])) ?></div>
                <div class="value" style="font-size:1.4rem"><?= $c['count'] ?></div>
                <?php if ($c['total_revenue'] > 0): ?>
                    <div class="delta"><?= e(Money::format($c['total_revenue'])) ?> revenue</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="get" action="<?= e(url('inventory')) ?>" class="row wrap mb-16px" style="gap:8px">
    <a href="<?= e(url('inventory')) ?>" class="btn <?= $category === '' ? '' : 'secondary' ?> sm">All</a>
    <?php foreach ($categories as $c): ?>
        <a href="<?= e(url('inventory?category=' . rawurlencode($c))) ?>" class="btn <?= $category === $c ? '' : 'secondary' ?> sm"><?= e($label($c)) ?></a>
    <?php endforeach; ?>
</form>

<?php if ($items === []): ?>
    <div class="empty">
        <span class="icon-box" style="width:64px;height:64px;border-radius:16px;background:var(--surface-2);color:var(--text-faint);display:grid;place-items:center;margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'boxes', 'class' => 'ico']) ?>
        </span>
        <div class="h3">No stock recorded</div>
        <p>Track harvested produce and input stock (seed, fertiliser, chemicals). Sales reduce stock and post to Finance automatically.</p>
        <?php if ($canManage): ?><p class="mt-16px"><a class="btn" href="<?= e(url('inventory/create')) ?>">Add a stock item</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="stack" style="--stack-gap:12px">
        <?php foreach ($items as $it):
            $cc = $catColor[$it['category']] ?? $catColor['other'];
            $stock = (float) $it['quantity'];
        ?>
            <div class="card">
                <div class="spread" style="padding:16px 20px;border-bottom:1px solid var(--line);background:var(--surface-2);align-items:flex-start">
                    <div class="row" style="gap:12px;align-items:flex-start">
                        <span style="width:48px;height:48px;border-radius:16px;background:<?= $cc['bg'] ?>;color:<?= $cc['fg'] ?>;display:grid;place-items:center;flex:none">
                            <?= $this->partial('partials/icon', ['name' => $catIcon[$it['category']] ?? 'boxes', 'class' => 'ico']) ?>
                        </span>
                        <div>
                            <h3 class="h3"><?= e((string) $it['name']) ?></h3>
                            <div class="row wrap" style="gap:6px;margin-top:4px">
                                <span class="badge" style="background:<?= $cc['bg'] ?>;color:<?= $cc['fg'] ?>;font-size:.625rem"><?= e($label((string) $it['category'])) ?></span>
                                <?php if (!empty($it['season'])): ?><span class="badge" style="background:#F0F9FF;color:#075985;font-size:.625rem"><?= e((string) $it['season']) ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($canManage): ?>
                        <div class="row" style="gap:4px;flex:none">
                            <a href="<?= e(url('inventory/' . rawurlencode((string) $it['id']) . '/sell')) ?>" class="row" style="gap:4px;height:32px;padding:0 12px;border-radius:9px;background:var(--teal-pale);color:var(--teal);font-size:.75rem;font-weight:800;border:1px solid #86EFAC">
                                <?= $this->partial('partials/icon', ['name' => 'wallet', 'class' => 'ico ico-sm']) ?> Sell
                            </a>
                            <a href="<?= e(url('inventory/' . rawurlencode((string) $it['id']) . '/edit')) ?>" title="Edit"
                               style="width:32px;height:32px;border-radius:9px;display:grid;place-items:center;background:var(--surface-3);color:var(--text-faint)">
                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                            </a>
                            <form method="post" action="<?= e(url('inventory/' . rawurlencode((string) $it['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this stock item?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Delete"
                                        style="width:32px;height:32px;border-radius:9px;display:grid;place-items:center;background:var(--red-050);color:var(--red-text);border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="grid cols-4" style="gap:10px">
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Available</p>
                            <p style="font-size:.8rem;font-weight:800;color:<?= $stock <= 0 ? 'var(--text-faint)' : 'var(--text)' ?>"><?= e(rtrim(rtrim(number_format($stock, 3), '0'), '.')) ?> <?= e((string) $it['unit']) ?></p>
                        </div>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Total sold</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= (float) $it['sold_qty'] > 0 ? e(rtrim(rtrim(number_format((float) $it['sold_qty'], 3), '0'), '.')) . ' ' . e((string) $it['unit']) : '—' ?></p>
                        </div>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Revenue</p>
                            <p style="font-size:.8rem;font-weight:800;color:<?= (float) $it['sold_revenue'] > 0 ? 'var(--green-text)' : 'var(--text-faint)' ?>"><?= (float) $it['sold_revenue'] > 0 ? e(Money::format((float) $it['sold_revenue'])) : '—' ?></p>
                        </div>
                        <div style="background:var(--surface-2);border:1px solid var(--line);border-radius:12px;padding:10px">
                            <p style="font-size:.625rem;font-weight:800;text-transform:uppercase;color:var(--text-faint);margin-bottom:2px">Acq. cost</p>
                            <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= $it['acquisition_unit_cost'] !== null ? e(Money::format((float) $it['acquisition_unit_cost'])) : '—' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
