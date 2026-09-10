<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var list<string> $categories
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$total = array_sum(array_map(static fn ($r) => (float) $r['amount'], $rows));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Overheads</h1>
        <p class="lede"><?= e(Money::format($total)) ?> recorded · rent, salaries, utilities, insurance, loans</p>
    </div>
    <a class="btn ghost" href="<?= e(url('finance')) ?>">← Finance</a>
</div>

<?php if ($canManage): ?>
<div class="card mb-24">
    <div class="card-head"><h2 class="h2">Add overhead</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('finance/overheads')) ?>" class="grid cols-2" style="gap:14px">
            <?= csrf_field() ?>
            <?= $this->partial('partials/field', ['name' => 'description', 'label' => 'Description', 'required' => true]) ?>
            <div class="field">
                <label for="f_category">Category</label>
                <select class="select" id="f_category" name="category">
                    <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
                </select>
            </div>
            <?= $this->partial('partials/field', ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
            <?= $this->partial('partials/field', ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true, 'value' => date('Y-m-d')]) ?>
            <label class="checkline" style="grid-column:1/-1">
                <input type="checkbox" name="recurring" value="1"> <span>Recurring cost</span>
            </label>
            <div style="grid-column:1/-1"><button type="submit" class="btn">Add overhead</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No overheads</div><p>Farm-wide costs that aren’t tied to one field or crop.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Date</th><th>Description</th><th>Category</th><th>Recurring</th><th class="num">Amount</th><?php if ($canManage): ?><th class="num"></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td class="small nowrap"><?= e(Dates::forDisplay((string) $r['date'])) ?></td>
                <td><?= e((string) $r['description']) ?></td>
                <td class="small"><?= e((string) $r['category']) ?></td>
                <td><?= (int) $r['recurring'] === 1 ? '<span class="badge blue">Recurring</span>' : '<span class="muted">—</span>' ?></td>
                <td class="num"><?= e(Money::format((float) $r['amount'])) ?></td>
                <?php if ($canManage): ?>
                    <td class="num">
                        <form method="post" action="<?= e(url('finance/overheads/' . rawurlencode((string) $r['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this overhead?')">
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
