<?php
/** @var array<string,mixed> $template */
$this->layout('layouts/app');
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <p class="small muted mb-8px"><a href="<?= e(url('templates')) ?>">&larr; Seasonal templates</a></p>
        <h1 class="h1"><?= e((string) $template['name']) ?></h1>
        <p class="lede"><?= e((string) $template['season']) ?> &middot; <?= e((string) $template['crop']) ?></p>
    </div>
</div>

<div class="card mb-16px"><div class="card-body">
    <p><?= e((string) $template['description']) ?></p>
</div></div>

<div class="h3 mb-16px">Activity schedule</div>
<div class="stack mb-16px" style="--stack-gap:8px">
    <?php foreach ($template['activities'] as $a): ?>
        <div class="card"><div class="card-body">
            <div class="spread" style="align-items:flex-start;margin-bottom:6px">
                <h3 class="h3" style="font-size:.95rem"><?= e((string) $a['activityType']) ?></h3>
                <span class="badge blue"><?= e((string) $a['timing']) ?></span>
            </div>
            <p class="small muted mb-8px"><?= e((string) $a['notes']) ?></p>
            <div class="row wrap" style="gap:6px">
                <?php foreach ($a['inputs'] as $input): ?>
                    <span class="badge"><?= e((string) $input) ?></span>
                <?php endforeach; ?>
            </div>
        </div></div>
    <?php endforeach; ?>
</div>

<div class="grid cols-2">
    <div>
        <div class="h3 mb-16px">Payroll roles</div>
        <div class="stack" style="--stack-gap:8px">
            <?php foreach ($template['payroll'] as $p): ?>
                <div class="card"><div class="card-body">
                    <div class="spread" style="margin-bottom:4px">
                        <p style="font-weight:800"><?= e((string) $p['role']) ?></p>
                        <span class="badge">per <?= e((string) $p['payRateUnit']) ?></span>
                    </div>
                    <p class="small muted"><?= e((string) $p['notes']) ?></p>
                </div></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <div class="h3 mb-16px">Sales evidence</div>
        <div class="stack" style="--stack-gap:8px">
            <?php foreach ($template['sales'] as $s): ?>
                <div class="card"><div class="card-body">
                    <p style="font-weight:800;margin-bottom:2px"><?= e((string) $s['description']) ?></p>
                    <p class="small muted mb-8px"><?= e((string) $s['category']) ?></p>
                    <ul style="margin:0;padding-left:18px">
                        <?php foreach ($s['evidence'] as $ev): ?>
                            <li class="small"><?= e((string) $ev) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="alert info mt-16px">
    <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
    <div>
        Good record packs for this workflow: <?= e(implode(', ', $template['recordPackHints'])) ?>.
        Build one from your actual records under <a href="<?= e(url('reports')) ?>">Reports</a>.
    </div>
</div>
<?php $this->stop(); ?>
