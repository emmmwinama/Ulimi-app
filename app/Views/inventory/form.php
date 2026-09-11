<?php
/** @var array<string,mixed>|null $item @var list<string> $categories */
$this->layout('layouts/app');
$editing = $item !== null;
$action = $editing ? url('inventory/' . rawurlencode((string) $item['id'])) : url('inventory');
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1"><?= $editing ? 'Edit stock item' : 'Add stock item' ?></h1></div></div>

<div class="content-narrow">
<form method="post" action="<?= e($action) ?>" class="card">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="card-body stack">
        <?= $this->partial('partials/inventory/item', ['item' => $item, 'categories' => $categories]) ?>
    </div>
    <div class="card-foot spread">
        <a class="btn ghost" href="<?= e(url('inventory')) ?>">Cancel</a>
        <button type="submit" class="btn"><?= $editing ? 'Save' : 'Add item' ?></button>
    </div>
</form>
</div>
<?php $this->stop(); ?>
