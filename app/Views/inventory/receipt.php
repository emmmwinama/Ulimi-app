<?php
/**
 * @var array<string,mixed> $sale (joined with item_name/item_category)
 */
$this->layout('layouts/print');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>

<div class="doc-section">
    <h2>Sale details</h2>
    <div class="table-wrap">
        <table class="data">
            <tbody>
                <tr><td><strong>Item</strong></td><td><?= e((string) $sale['item_name']) ?></td></tr>
                <tr><td><strong>Quantity</strong></td><td><?= e(rtrim(rtrim((string) $sale['quantity_sold'], '0'), '.')) ?> <?= e((string) $sale['unit']) ?></td></tr>
                <tr><td><strong>Price per unit</strong></td><td><?= e(Money::format((float) $sale['price_per_unit'])) ?></td></tr>
                <tr><td><strong>Total</strong></td><td><strong><?= e(Money::format((float) $sale['total_amount'])) ?></strong></td></tr>
                <tr><td><strong>Sale date</strong></td><td><?= e(Dates::forDisplay((string) $sale['sale_date'])) ?></td></tr>
                <tr><td><strong>Buyer</strong></td><td><?= e((string) ($sale['buyer_name'] ?? '—')) ?></td></tr>
                <?php if (!empty($sale['collection_point'])): ?>
                    <tr><td><strong>Collection point</strong></td><td><?= e((string) $sale['collection_point']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($sale['transport_method'])): ?>
                    <tr><td><strong>Transport</strong></td><td><?= e((string) $sale['transport_method']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($sale['pickup_date'])): ?>
                    <tr><td><strong>Pickup date</strong></td><td><?= e(Dates::forDisplay((string) $sale['pickup_date'])) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($sale['notes'])): ?>
                    <tr><td><strong>Notes</strong></td><td><?= e((string) $sale['notes']) ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->stop(); ?>
