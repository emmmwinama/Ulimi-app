<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<int,array<string,mixed>> $pending */
$this->layout('layouts/admin');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Market data</h1>
        <p class="lede">Staple crop prices auto-fetched from WFP's open Malawi food-price data; cash crops (tobacco, cotton, sunflower, sugarcane) stay manual — no reliable live source exists for those.</p>
    </div>
    <div class="row" style="gap:8px">
        <form method="post" action="<?= e(url('admin/market/check-now')) ?>">
            <?= csrf_field() ?>
            <button class="btn secondary" type="submit"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico ico-sm']) ?> Check for updates</button>
        </form>
        <a class="btn" href="#add-price"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Add price</a>
    </div>
</div>

<?php if ($pending !== []): ?>
    <div class="card mb-16px">
        <div class="card-head">
            <h2 class="h3">Pending updates <span class="badge amber"><?= count($pending) ?></span></h2>
        </div>
        <div class="card-body stack" style="--stack-gap:10px">
            <?php foreach ($pending as $p):
                $isNew = $p['current_price_avg'] === null;
                $delta = $isNew ? 0.0 : (float) $p['price_avg'] - (float) $p['current_price_avg'];
            ?>
                <div class="spread" style="padding:12px 14px;background:var(--surface-2);border:1px solid var(--line);border-radius:var(--radius);flex-wrap:wrap;gap:10px">
                    <div>
                        <p class="h4"><?= e((string) $p['crop_name']) ?> — <?= e((string) $p['region']) ?></p>
                        <p class="small muted"><?= e((string) $p['market']) ?> · recorded <?= e(Dates::forDisplay((string) $p['recorded_at'])) ?></p>
                    </div>
                    <div class="row" style="gap:16px">
                        <div>
                            <p class="eyebrow" style="margin-bottom:2px">Avg price</p>
                            <?php if ($isNew): ?>
                                <p class="h3"><?= e(Money::format((float) $p['price_avg'], (string) $p['currency'])) ?> <span class="badge blue">new</span></p>
                            <?php else: ?>
                                <p class="h3">
                                    <span class="muted" style="text-decoration:line-through;font-weight:600"><?= e(Money::format((float) $p['current_price_avg'], (string) $p['currency'])) ?></span>
                                    &rarr; <?= e(Money::format((float) $p['price_avg'], (string) $p['currency'])) ?>
                                    <span class="badge <?= $delta > 0 ? 'green' : ($delta < 0 ? 'red' : '') ?>"><?= $delta >= 0 ? '+' : '-' ?><?= e(Money::format(abs($delta), (string) $p['currency'])) ?></span>
                                </p>
                            <?php endif; ?>
                            <p class="small muted">Range <?= e(Money::format((float) $p['price_min'], (string) $p['currency'])) ?> – <?= e(Money::format((float) $p['price_max'], (string) $p['currency'])) ?></p>
                        </div>
                        <div class="row" style="gap:6px">
                            <form method="post" action="<?= e(url('admin/market/updates/' . rawurlencode((string) $p['id']) . '/approve')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn sm" type="submit">Approve</button>
                            </form>
                            <form method="post" action="<?= e(url('admin/market/updates/' . rawurlencode((string) $p['id']) . '/reject')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn sm ghost" type="submit">Dismiss</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="table-wrap"><table class="data">
    <thead><tr><th>Crop</th><th>Market</th><th class="num">Avg</th><th>Updated</th><th>Active</th><th class="num">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e((string) $r['crop_name']) ?></td>
            <td class="small"><?= e((string) $r['market']) ?></td>
            <td class="num"><?= e(Money::format((float) $r['price_avg'], (string) $r['currency'])) ?></td>
            <td class="small muted"><?= e(Dates::forDisplay((string) $r['recorded_at'])) ?></td>
            <td><span class="badge <?= (int) $r['is_active'] === 1 ? 'green' : '' ?>"><?= (int) $r['is_active'] === 1 ? 'Yes' : 'No' ?></span></td>
            <td class="num nowrap">
                <a class="btn sm ghost" href="#edit-price-<?= e((string) $r['id']) ?>">Edit</a>
                <form method="post" action="<?= e(url('admin/market/' . rawurlencode((string) $r['id']) . '/delete')) ?>" style="display:inline" onsubmit="return confirm('Delete this price?')">
                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>

<div class="slide-over" id="add-price">
    <a href="#" class="scrim" aria-label="Close"></a>
    <div class="panel">
        <div class="panel-head">
            <div>
                <h2 class="h3">Add a price</h2>
                <p class="small muted mt-8px">Record a market price observation</p>
            </div>
            <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
        </div>
        <form method="post" action="<?= e(url('admin/market')) ?>" style="display:contents">
            <?= csrf_field() ?>
            <div class="panel-body stack">
                <?= $this->partial('partials/admin/market-price-fields', ['row' => null]) ?>
            </div>
            <div class="panel-foot">
                <a href="#" class="btn ghost block">Cancel</a>
                <button type="submit" class="btn block">Add price</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($rows as $r): ?>
    <div class="slide-over" id="edit-price-<?= e((string) $r['id']) ?>">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Edit price</h2>
                    <p class="small muted mt-8px"><?= e((string) $r['crop_name']) ?> — <?= e((string) $r['market']) ?></p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('admin/market/' . rawurlencode((string) $r['id']))) ?>" style="display:contents">
                <?= csrf_field() ?>
                <?= method_field('PUT') ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/admin/market-price-fields', ['row' => $r]) ?>
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
