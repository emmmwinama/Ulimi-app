<?php
/**
 * @var array<string,array{label:string,sections:list<string>}> $packs
 * @var bool $canBuild
 * @var array{income:float,expense_tx:float,activity_cost:float,overhead:float,total_cost:float,net:float,by_category:array<string,float>} $totals
 * @var list<array{month:string,income:float,cost:float}> $trend
 * @var float $yieldKg
 * @var list<array{name:string,net:float}> $seasons
 * @var list<array{name:string,area:float}> $crops
 * @var string|null $insight
 */
$this->layout('layouts/app');
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Reports</h1>
        <p class="lede">Evidence packs, trends, compliance and credit-readiness — built from your real records.</p>
    </div>
</div>

<?php if ($insight !== null): ?>
    <div style="background:linear-gradient(135deg,#0F172A 0%,#111827 100%);border-radius:16px;padding:20px 24px;margin-bottom:16px;color:#fff">
        <div class="row" style="gap:8px;margin-bottom:8px">
            <?= $this->partial('partials/icon', ['name' => 'shield-check', 'class' => 'ico ico-sm']) ?>
            <span style="font-size:.7rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.55)">AI insight</span>
        </div>
        <p style="font-size:.9rem;line-height:1.6;color:rgba(255,255,255,.92);white-space:pre-line"><?= e($insight) ?></p>
    </div>
<?php endif; ?>

<div class="grid cols-4 mb-16px">
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--teal-pale)"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.3rem;color:var(--teal)"><?= e(Money::compact($totals['income'])) ?></div>
        <div class="label">Income</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--red-050);color:var(--red-text)"><?= $this->partial('partials/icon', ['name' => 'trend-down', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.3rem"><?= e(Money::compact($totals['total_cost'])) ?></div>
        <div class="label">Total cost</div>
    </div>
    <div class="stat">
        <div class="icon-row">
            <span class="icon-box" style="background:<?= $totals['net'] >= 0 ? 'var(--teal-pale)' : 'var(--red-050)' ?>;color:<?= $totals['net'] >= 0 ? 'var(--teal)' : 'var(--red-text)' ?>">
                <?= $this->partial('partials/icon', ['name' => $totals['net'] >= 0 ? 'trend-up' : 'trend-down', 'class' => 'ico']) ?>
            </span>
        </div>
        <div class="value" style="font-size:1.3rem;color:<?= $totals['net'] >= 0 ? 'var(--teal)' : 'var(--red)' ?>"><?= e(Money::compact($totals['net'])) ?></div>
        <div class="label">Net margin</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--blue-050);color:var(--blue)"><?= $this->partial('partials/icon', ['name' => 'wheat', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.3rem"><?= e(number_format($yieldKg)) ?> kg</div>
        <div class="label">Total yield</div>
    </div>
</div>

<div class="card mb-16px">
    <div class="card-head"><h2 class="h2">Income vs cost — trailing 12 months</h2></div>
    <div class="card-body">
        <?= $this->partial('partials/barchart', ['series' => $trend]) ?>
    </div>
</div>

<?php if ($seasons !== [] || $crops !== []): ?>
<div class="grid cols-2 mb-16px">
    <div class="card"><div class="card-body">
        <p class="eyebrow mb-16px">Season profitability</p>
        <?php if ($seasons === []): ?>
            <p class="small muted">No seasonal transactions recorded yet.</p>
        <?php else: ?>
            <div class="stack" style="--stack-gap:10px">
                <?php
                $maxSeason = max(array_map(static fn ($s) => abs($s['net']), $seasons) ?: [1]) ?: 1;
                foreach ($seasons as $s):
                    $width = max(6, (int) round(abs($s['net']) / $maxSeason * 100));
                    $positive = $s['net'] >= 0;
                ?>
                    <div>
                        <div class="spread small" style="font-weight:700;color:var(--text-soft);margin-bottom:4px">
                            <span><?= e($s['name']) ?></span>
                            <span style="color:<?= $positive ? 'var(--teal)' : 'var(--red)' ?>"><?= $positive ? '+' : '-' ?><?= e(Money::format(abs($s['net']))) ?></span>
                        </div>
                        <div style="height:10px;background:var(--surface-3);border-radius:999px;overflow:hidden">
                            <div style="height:100%;width:<?= $width ?>%;background:<?= $positive ? 'var(--teal)' : 'var(--red)' ?>;border-radius:999px"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>

    <div class="card"><div class="card-body">
        <p class="eyebrow mb-16px">Crop area mix</p>
        <?php if ($crops === []): ?>
            <p class="small muted">No crops planted yet.</p>
        <?php else: ?>
            <div class="stack" style="--stack-gap:10px">
                <?php
                $maxArea = max(array_map(static fn ($c) => (float) $c['area'], $crops) ?: [1]) ?: 1;
                foreach ($crops as $c):
                    $width = max(6, (int) round((float) $c['area'] / $maxArea * 100));
                ?>
                    <div>
                        <div class="spread small" style="font-weight:700;color:var(--text-soft);margin-bottom:4px">
                            <span><?= e((string) $c['name']) ?></span>
                            <span><?= e(number_format((float) $c['area'], 1)) ?> ha</span>
                        </div>
                        <div style="height:10px;background:var(--surface-3);border-radius:999px;overflow:hidden">
                            <div style="height:100%;width:<?= $width ?>%;background:var(--blue);border-radius:999px"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>
</div>
<?php endif; ?>

<div class="grid cols-2 mb-24px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Record packs</h2></div>
        <div class="card-body">
            <p class="small muted mb-16px">Curated, printable evidence for a specific audience. Open one, filter by season, then use your browser’s Print to save as PDF.</p>
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
                <a class="btn block mt-8px" href="<?= e(url('reports/builder')) ?>">
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
