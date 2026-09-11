<?php
/**
 * @var array<string,mixed>|null $row
 * @var array<int,array<string,mixed>> $fields @var array<int,array<string,mixed>> $crops
 * @var list<string> $incomeCats @var list<string> $expenseCats
 */
$this->layout('layouts/app');
$editing = $row !== null;
$action = $editing ? url('finance/transactions/' . rawurlencode((string) $row['id'])) : url('finance/transactions');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit transaction' : 'Add transaction' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/finance/transaction', [
            'row' => $row, 'fields' => $fields, 'crops' => $crops, 'incomeCats' => $incomeCats, 'expenseCats' => $expenseCats,
        ]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('finance/transactions')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add transaction' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
