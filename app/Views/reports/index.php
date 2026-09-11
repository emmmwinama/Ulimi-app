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
                    <a href="<?= e(url('reports/pack/' . $key)) ?>" class="row" style="gap:10px;padding:12px 14px;border-radius:16px;background:var(--surface-2);border:1px solid var(--line)">
                        <span style="width:32px;height:32px;border-radius:10px;background:var(--teal-pale);color:var(--teal);display:grid;place-items:center;flex:none">
                            <?= $this->partial('partials/icon', ['name' => 'file-text', 'class' => 'ico ico-sm']) ?>
                        </span>
                        <span class="small" style="font-weight:800;color:var(--text)"><?= e($p['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Analytics</h2></div>
        <div class="card-body stack">
            <?php
            $analytics = [
                ['href' => url('reports/trends'), 'icon' => 'bar-chart', 'label' => 'Cashflow trends'],
                ['href' => url('reports/compliance'), 'icon' => 'check-circle', 'label' => 'Compliance checklist'],
                ['href' => url('reports/credit-score'), 'icon' => 'trend-up', 'label' => 'Credit readiness score'],
            ];
            foreach ($analytics as $a):
            ?>
                <a href="<?= e($a['href']) ?>" class="row" style="gap:10px;padding:12px 14px;border-radius:16px;background:var(--surface-2);border:1px solid var(--line)">
                    <span style="width:32px;height:32px;border-radius:10px;background:var(--blue-050);color:#1E40AF;display:grid;place-items:center;flex:none">
                        <?= $this->partial('partials/icon', ['name' => $a['icon'], 'class' => 'ico ico-sm']) ?>
                    </span>
                    <span class="small" style="font-weight:800;color:var(--text)"><?= e($a['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if ($canBuild): ?>
                <a class="btn block mt-8" href="<?= e(url('reports/builder')) ?>">
                    <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Custom report builder
                </a>
            <?php else: ?>
                <div class="alert info"><?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
                    <div>Custom reports aren’t included in your current plan.</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
