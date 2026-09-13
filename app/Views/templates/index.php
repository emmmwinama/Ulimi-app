<?php
/** @var list<array<string,mixed>> $templates */
$this->layout('layouts/app');
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Seasonal templates</h1>
        <p class="lede">Practical crop workflows — activities, payroll roles and sales evidence to plan around.</p>
    </div>
</div>

<div class="grid cols-3">
    <?php foreach ($templates as $t): ?>
        <a class="card" href="<?= e(url('templates/' . rawurlencode((string) $t['id']))) ?>" style="text-decoration:none;color:inherit;display:flex;flex-direction:column">
            <div class="card-body" style="flex:1">
                <div class="row" style="gap:10px;margin-bottom:10px">
                    <span class="icon-box teal">
                        <?= $this->partial('partials/icon', ['name' => 'wheat', 'class' => 'ico']) ?>
                    </span>
                    <div>
                        <h3 class="h3"><?= e((string) $t['name']) ?></h3>
                        <p style="font-size:.75rem;color:var(--text-faint)"><?= e((string) $t['season']) ?></p>
                    </div>
                </div>
                <p class="small muted"><?= e((string) $t['description']) ?></p>
            </div>
            <div class="spread card-foot">
                <span class="badge green"><?= e((string) $t['crop']) ?></span>
                <span class="small muted"><?= count($t['activities']) ?> activities</span>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php $this->stop(); ?>
