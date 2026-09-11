<?php
/**
 * @var array<string,array{label:string,sections:list<string>}> $packs
 * @var bool $canBuild
 */
$this->layout('layouts/app');
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Reports</h1>
        <p class="lede">Evidence packs, trends, compliance and credit-readiness — built from your real records.</p>
    </div>
</div>

<div class="grid cols-2 mb-24">
    <div class="card">
        <div class="card-head"><h2 class="h2">Record packs</h2></div>
        <div class="card-body">
            <p class="small muted mb-16">Curated, printable evidence for a specific audience. Open one, filter by season, then use your browser’s Print to save as PDF.</p>
            <div class="grid cols-2" style="gap:10px">
                <?php foreach ($packs as $key => $p): ?>
                    <a class="btn secondary" href="<?= e(url('reports/pack/' . $key)) ?>" style="justify-content:flex-start"><?= e($p['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Analytics</h2></div>
        <div class="card-body stack">
            <a class="btn secondary block" href="<?= e(url('reports/trends')) ?>">Cashflow trends</a>
            <a class="btn secondary block" href="<?= e(url('reports/compliance')) ?>">Compliance checklist</a>
            <a class="btn secondary block" href="<?= e(url('reports/credit-score')) ?>">Credit readiness score</a>
            <?php if ($canBuild): ?>
                <a class="btn block" href="<?= e(url('reports/builder')) ?>">Custom report builder</a>
            <?php else: ?>
                <div class="alert info"><?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
                    <div>Custom reports aren’t included in your current plan.</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
