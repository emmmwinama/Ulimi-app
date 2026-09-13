<?php
/**
 * @var array<int,array<string,mixed>> $prices
 * @var list<string> $crops @var string $crop
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Market prices</h1>
        <p class="lede">Reference prices to compare before you sell.</p>
    </div>
</div>

<div class="row wrap mb-16px" style="gap:8px">
    <a href="<?= e(url('market')) ?>" class="btn sm">Prices</a>
    <a href="<?= e(url('market/buyers')) ?>" class="btn sm secondary">Buyers &amp; Offers</a>
</div>

<form method="get" action="<?= e(url('market')) ?>" class="mb-24px">
    <select class="select" name="crop" onchange="this.form.submit()" style="max-width:220px">
        <option value="">All crops</option>
        <?php foreach ($crops as $c): ?><option value="<?= e($c) ?>" <?= $c === $crop ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
</form>

<?php if ($prices === []): ?>
    <div class="empty"><div class="h3">No price data</div><p>Reference prices will appear here once published.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Crop</th><th>Market</th><th>Region</th><th class="num">Min</th><th class="num">Avg</th><th class="num">Max</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($prices as $p): ?>
            <tr>
                <td><strong><?= e((string) $p['crop_name']) ?></strong><?= $p['variety'] ? ' <span class="muted small">' . e((string) $p['variety']) . '</span>' : '' ?></td>
                <td><?= e((string) $p['market']) ?></td>
                <td class="small muted"><?= e((string) $p['region']) ?></td>
                <td class="num"><?= e(Money::format((float) $p['price_min'], (string) $p['currency'])) ?></td>
                <td class="num"><strong><?= e(Money::format((float) $p['price_avg'], (string) $p['currency'])) ?></strong> <span class="muted small">/<?= e((string) $p['unit']) ?></span></td>
                <td class="num"><?= e(Money::format((float) $p['price_max'], (string) $p['currency'])) ?></td>
                <td class="small muted"><?= e(Dates::forDisplay((string) $p['recorded_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="hint mt-16px">Source: ADMARC / Tobacco Control Commission reference prices, refreshed periodically by AgriVault admins.</p>
<?php endif; ?>
<?php $this->stop(); ?>
