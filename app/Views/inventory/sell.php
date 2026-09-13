<?php
/**
 * @var array<string,mixed> $item
 * @var array<int,array<string,mixed>> $sales
 * @var array<int,array<string,mixed>> $buyers
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$id = rawurlencode((string) $item['id']);
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Sell <?= e((string) $item['name']) ?></h1>
        <p class="lede"><?= e(rtrim(rtrim(number_format((float) $item['quantity'], 3), '0'), '.')) ?> <?= e((string) $item['unit']) ?> in stock</p>
    </div>
    <a class="btn ghost" href="<?= e(url('inventory')) ?>">← Inventory</a>
</div>

<div class="content-narrow">
<div class="card mb-24px">
    <div class="card-head"><h2 class="h2">Record a sale</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('inventory/' . $id . '/sell')) ?>" class="grid cols-2" style="gap:14px">
            <?= csrf_field() ?>
            <?= $this->partial('partials/field', ['name' => 'quantity_sold', 'label' => 'Quantity sold (' . e((string) $item['unit']) . ')', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
            <?= $this->partial('partials/field', ['name' => 'price_per_unit', 'label' => 'Price per unit', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
            <?= $this->partial('partials/field', ['name' => 'sale_date', 'label' => 'Sale date', 'type' => 'date', 'required' => true, 'value' => date('Y-m-d')]) ?>
            <?php if ($buyers !== []): ?>
                <div class="field">
                    <label for="f_buyer_id">Saved buyer (optional)</label>
                    <select class="select" id="f_buyer_id" name="buyer_id">
                        <option value="">—</option>
                        <?php foreach ($buyers as $b): ?><option value="<?= e((string) $b['id']) ?>"><?= e((string) $b['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?= $this->partial('partials/field', ['name' => 'buyer_name', 'label' => 'Or type a buyer name (optional)']) ?>
            <?= $this->partial('partials/field', ['name' => 'collection_point', 'label' => 'Collection point (optional)', 'placeholder' => 'Where the buyer collects it']) ?>
            <?= $this->partial('partials/field', ['name' => 'transport_method', 'label' => 'Transport (optional)', 'placeholder' => 'Own truck, buyer pickup, courier']) ?>
            <?= $this->partial('partials/field', ['name' => 'pickup_date', 'label' => 'Pickup date (optional)', 'type' => 'date']) ?>
            <div class="field" style="grid-column:1/-1">
                <label for="f_notes">Notes</label>
                <textarea class="textarea" id="f_notes" name="notes" rows="2"></textarea>
            </div>
            <div style="grid-column:1/-1"><button type="submit" class="btn">Record sale</button></div>
        </form>
        <p class="hint mt-8px">Recording a sale reduces stock and creates a matching income transaction in Finance.</p>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Sale history</h2></div>
    <div class="table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>Date</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th><th>Buyer</th><th class="num">Receipt</th></tr></thead>
            <tbody>
            <?php if ($sales === []): ?>
                <tr><td colspan="6" class="muted">No sales yet.</td></tr>
            <?php else: foreach ($sales as $s): ?>
                <tr>
                    <td class="small"><?= e(Dates::forDisplay((string) $s['sale_date'])) ?></td>
                    <td class="num"><?= e(rtrim(rtrim((string) $s['quantity_sold'], '0'), '.')) ?> <?= e((string) $s['unit']) ?></td>
                    <td class="num"><?= e(Money::format((float) $s['price_per_unit'])) ?></td>
                    <td class="num"><?= e(Money::format((float) $s['total_amount'])) ?></td>
                    <td class="muted">
                        <?= e((string) ($s['buyer_name'] ?: '—')) ?>
                        <?php if (!empty($s['collection_point']) || !empty($s['transport_method'])): ?>
                            <div class="small"><?= e(trim(((string) ($s['collection_point'] ?? '')) . (!empty($s['transport_method']) ? ' · ' . (string) $s['transport_method'] : ''))) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="num"><a class="btn sm ghost" href="<?= e(url('inventory/' . $id . '/sell/' . rawurlencode((string) $s['id']) . '/receipt')) ?>">Receipt</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<?php $this->stop(); ?>
