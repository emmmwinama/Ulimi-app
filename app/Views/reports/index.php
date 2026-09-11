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
 * @var string $tab
 * @var array{season:string,archived:string,field_id:string,crop_field_id:string,from:string,to:string} $filters
 * @var array<string,mixed> $dashboard
 * @var string $compareA
 * @var string $compareB
 * @var array<int,array<string,mixed>> $filterFields
 * @var array<int,array<string,mixed>> $filterCrops
 */
$this->layout('layouts/app');
use App\Support\Money;

$tabUrl = static function (string $t) use ($filters, $compareA, $compareB) {
    $q = array_filter(array_merge($filters, ['tab' => $t, 'compare_a' => $compareA, 'compare_b' => $compareB]), static fn ($v) => $v !== '');
    return url('reports') . '?' . http_build_query($q);
};

$tabs = [
    'overview'    => 'Overview',
    'crops'       => 'Crop summary',
    'finance'     => 'Financials',
    'analytics'   => 'Analytics',
    'yields'      => 'Yields',
    'overhead'    => 'Overhead',
    'trends'      => 'Yield trends',
    'performance' => 'Crop performance',
    'breakeven'   => 'Break-even',
    'comparison'  => 'Season compare',
];

$hasActiveFilters = $filters['season'] !== '' || $filters['archived'] !== 'active' || $filters['field_id'] !== ''
    || $filters['crop_field_id'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Reports</h1>
        <p class="lede">Costs from activities · overhead allocated by area · revenue from sales.</p>
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

<form method="get" action="<?= e(url('reports')) ?>" class="row wrap mb-16px" style="gap:10px;align-items:flex-end">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">Season</label>
        <select class="select" name="season" onchange="this.form.submit()" style="max-width:220px">
            <option value="">All seasons</option>
            <?php foreach ($dashboard['all_seasons'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $filters['season'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">Records</label>
        <select class="select" name="archived" onchange="this.form.submit()" style="max-width:160px">
            <option value="active" <?= $filters['archived'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="archived" <?= $filters['archived'] === 'archived' ? 'selected' : '' ?>>Archived</option>
            <option value="both" <?= $filters['archived'] === 'both' ? 'selected' : '' ?>>Both</option>
        </select>
    </div>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">Field</label>
        <select class="select" name="field_id" onchange="this.form.submit()" style="max-width:200px">
            <option value="">All fields</option>
            <?php foreach ($filterFields as $f): ?>
                <option value="<?= e((string) $f['id']) ?>" <?= $filters['field_id'] === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">Crop</label>
        <select class="select" name="crop_field_id" onchange="this.form.submit()" style="max-width:220px">
            <option value="">All crops</option>
            <?php foreach ($filterCrops as $c): ?>
                <?php if ($filters['field_id'] !== '' && (string) $c['field_id'] !== $filters['field_id']) continue; ?>
                <option value="<?= e((string) $c['id']) ?>" <?= $filters['crop_field_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                    <?= e((string) $c['crop_name']) ?><?= $c['variety'] ? ' ' . e((string) $c['variety']) : '' ?> — <?= e((string) $c['field_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">From</label>
        <input class="input" type="date" name="from" value="<?= e($filters['from']) ?>" style="max-width:160px">
    </div>
    <div>
        <label class="hint" style="display:block;margin-bottom:4px">To</label>
        <input class="input" type="date" name="to" value="<?= e($filters['to']) ?>" style="max-width:160px">
    </div>
    <button class="btn secondary sm" type="submit">Filter</button>
    <?php if ($hasActiveFilters): ?><a class="btn ghost sm" href="<?= e(url('reports') . '?tab=' . e($tab)) ?>">Clear filters</a><?php endif; ?>
</form>

<div class="tabs mb-16px">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="tab <?= $tab === $key ? 'active' : '' ?>" href="<?= e($tabUrl($key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?= $this->partial('partials/reports/tabs/' . $tab, [
    'dashboard' => $dashboard, 'compareA' => $compareA, 'compareB' => $compareB,
    'packs' => $packs, 'canBuild' => $canBuild, 'filters' => $filters,
]) ?>
<?php $this->stop(); ?>
