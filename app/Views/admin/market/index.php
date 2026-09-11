<?php
/** @var array<int,array<string,mixed>> $rows */
$this->layout('layouts/admin');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1">Market data</h1></div></div>

<div class="card mb-24">
    <div class="card-head"><h2 class="h2">Add a price</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('admin/market')) ?>" class="grid cols-4" style="gap:10px">
            <?= csrf_field() ?>
            <div class="field"><label class="small">Crop</label><input class="input" name="crop_name" required></div>
            <div class="field"><label class="small">Variety</label><input class="input" name="variety"></div>
            <div class="field"><label class="small">Unit</label><input class="input" name="unit" value="kg" required></div>
            <div class="field"><label class="small">Currency</label><input class="input" name="currency" value="MWK" maxlength="3" required></div>
            <div class="field"><label class="small">Min</label><input class="input" type="number" step="any" name="price_min" required></div>
            <div class="field"><label class="small">Avg</label><input class="input" type="number" step="any" name="price_avg" required></div>
            <div class="field"><label class="small">Max</label><input class="input" type="number" step="any" name="price_max" required></div>
            <div class="field"><label class="small">Market</label><input class="input" name="market" required></div>
            <div class="field"><label class="small">Region</label><input class="input" name="region" required></div>
            <label class="checkline" style="align-self:end"><input type="checkbox" name="is_active" value="1" checked> Active</label>
            <div style="grid-column:1/-1"><button class="btn" type="submit">Add price</button></div>
        </form>
    </div>
</div>

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
            <td class="num">
                <form method="post" action="<?= e(url('admin/market/' . rawurlencode((string) $r['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this price?')">
                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php $this->stop(); ?>
