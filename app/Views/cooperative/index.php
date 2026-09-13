<?php
/**
 * @var array<int,array<string,mixed>> $cooperatives
 */
$this->layout('layouts/app');
$label = static fn (string $s): string => ucfirst($s);
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Cooperatives</h1>
        <p class="lede">Pool inventory, production, and sales with other farms you trust.</p>
    </div>
</div>

<?php if ($cooperatives === []): ?>
    <div class="empty"><div class="h3">Not part of a cooperative yet</div><p>Create one for your group, or join an existing one with its code.</p></div>
<?php else: ?>
    <div class="stack mb-24px" style="--stack-gap:10px">
        <?php foreach ($cooperatives as $c): ?>
            <a href="<?= e(url('cooperatives/' . rawurlencode((string) $c['id']))) ?>" class="card" style="display:block;padding:16px 20px">
                <div class="spread">
                    <div>
                        <h3 class="h3"><?= e((string) $c['name']) ?></h3>
                        <p class="small muted"><?= e((string) ($c['region'] ?? '')) ?></p>
                    </div>
                    <span class="badge"><?= e($label((string) $c['role'])) ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid cols-2" style="gap:20px;align-items:start">
    <div class="card">
        <div class="card-head"><h2 class="h2">Create a cooperative</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('cooperatives')) ?>" class="stack">
                <?= csrf_field() ?>
                <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Name', 'required' => true]) ?>
                <?= $this->partial('partials/field', ['name' => 'region', 'label' => 'Region (optional)']) ?>
                <button class="btn" type="submit">Create</button>
            </form>
            <p class="hint mt-8px">Your farm becomes its chair. You'll get a join code to share with other farms.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="h2">Join a cooperative</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('cooperatives/join')) ?>" class="stack">
                <?= csrf_field() ?>
                <?= $this->partial('partials/field', ['name' => 'join_code', 'label' => 'Join code', 'required' => true, 'placeholder' => 'e.g. 4F2A9C1B']) ?>
                <button class="btn" type="submit">Join</button>
            </form>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
