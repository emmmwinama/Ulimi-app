<?php
/**
 * @var array<string,mixed> $a
 * @var float $labourSum @var float $inputSum @var float $otherSum @var float $grandTotal
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$id = rawurlencode((string) $a['id']);
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) $a['activity_type']) ?></h1>
        <p class="lede"><?= e(Dates::forDisplay((string) $a['date'])) ?> · <?= e((string) $a['field_name']) ?>
            <?= $a['crop_name'] ? ' · ' . e((string) $a['crop_name']) : '' ?></p>
    </div>
    <?php if ($canManage): ?>
        <div class="row">
            <a class="btn secondary" href="<?= e(url('activities/' . $id . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(url('activities/' . $id . '/delete')) ?>" onsubmit="return confirm('Delete this activity?')">
                <?= csrf_field() ?>
                <button class="btn ghost danger" type="submit">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($a['responsible_employee_name'] || $a['responsible_person_name']): ?>
    <p class="muted mb-16">Responsible: <strong><?= e((string) ($a['responsible_employee_name'] ?: $a['responsible_person_name'])) ?></strong></p>
<?php endif; ?>
<?php if ($a['notes']): ?><p class="mb-16"><?= nl2br(e((string) $a['notes'])) ?></p><?php endif; ?>

<div class="grid cols-4 mb-24">
    <div class="stat"><div class="label">Labour</div><div class="value" style="font-size:1.1rem"><?= e(Money::format($labourSum)) ?></div></div>
    <div class="stat"><div class="label">Inputs</div><div class="value" style="font-size:1.1rem"><?= e(Money::format($inputSum)) ?></div></div>
    <div class="stat"><div class="label">Other</div><div class="value" style="font-size:1.1rem"><?= e(Money::format($otherSum)) ?></div></div>
    <div class="stat"><div class="label">Total</div><div class="value" style="font-size:1.1rem"><?= e(Money::format($grandTotal)) ?></div></div>
</div>

<?php if ($a['labour']): ?>
<div class="card mb-16"><div class="card-head"><h2 class="h2">Labour</h2></div>
    <div class="table-wrap" style="border:0"><table class="data">
        <thead><tr><th>Worker</th><th class="num">Hours</th><th class="num">Days</th><th class="num">Cost</th></tr></thead>
        <tbody>
        <?php foreach ($a['labour'] as $l): ?>
            <tr>
                <td><?= e((string) ($l['employee_name'] ?: $l['worker_name'] ?: '—')) ?></td>
                <td class="num"><?= e(rtrim(rtrim((string) $l['hours_worked'], '0'), '.')) ?: '—' ?></td>
                <td class="num"><?= e(rtrim(rtrim((string) $l['days_worked'], '0'), '.')) ?: '—' ?></td>
                <td class="num"><?= e(Money::format((float) $l['total_cost'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<?php if ($a['inputs']): ?>
<div class="card mb-16"><div class="card-head"><h2 class="h2">Inputs</h2></div>
    <div class="table-wrap" style="border:0"><table class="data">
        <thead><tr><th>Input</th><th>Category</th><th class="num">Qty</th><th class="num">Unit cost</th><th class="num">Total</th></tr></thead>
        <tbody>
        <?php foreach ($a['inputs'] as $i): ?>
            <tr>
                <td><?= e((string) $i['input_name']) ?></td>
                <td class="muted"><?= e((string) $i['category']) ?></td>
                <td class="num"><?= e(rtrim(rtrim((string) $i['quantity'], '0'), '.')) ?> <?= e((string) $i['unit']) ?></td>
                <td class="num"><?= e(Money::format((float) $i['unit_cost'])) ?></td>
                <td class="num"><?= e(Money::format((float) $i['total_cost'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>

<?php if ($a['other']): ?>
<div class="card"><div class="card-head"><h2 class="h2">Other costs</h2></div>
    <div class="table-wrap" style="border:0"><table class="data">
        <thead><tr><th>Description</th><th class="num">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($a['other'] as $o): ?>
            <tr><td><?= e((string) $o['description']) ?></td><td class="num"><?= e(Money::format((float) $o['amount'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php endif; ?>
<?php $this->stop(); ?>
