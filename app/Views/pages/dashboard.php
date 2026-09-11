<?php
/**
 * @var App\Core\FarmContext $ctx
 * @var array<string,mixed>|null $subscription
 * @var string $userName
 * @var array<string,int|float> $stats
 * @var array<int,array{name:string,netRevenue:float}> $seasonProfit
 * @var array<int,array{name:string,totalArea:float,statuses:array<int,string>}> $cropSummary
 * @var array<int,array{name:string,cultivatableArea:float,allocated:float}> $fieldLandUse
 * @var array<int,array<string,mixed>> $recentActivities
 * @var array<int,array<string,mixed>> $upcoming
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;

$sub = $subscription;
[$statusText, $statusCls] = [
    'trial'     => ['Trial', 'blue'],
    'active'    => ['Active', 'green'],
    'past_due'  => ['Past due — read only', 'amber'],
    'expired'   => ['Expired — read only', 'red'],
    'suspended' => ['Suspended', 'red'],
][$ctx->subscriptionStatus()] ?? ['—', ''];

$hour = (int) date('G');
$greeting = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
$firstName = trim((string) strtok($userName !== '' ? $userName : 'there', ' '));

$netPositive = $stats['net'] >= 0;
$hasAnyRecords = $stats['fields'] > 0 || ($stats['active_crops'] + $stats['harvested_crops']) > 0
    || $stats['team'] > 1 || $stats['total_inventory'] > 0 || $recentActivities !== [];

$onboarding = [
    ['label' => 'Create farm',       'href' => url('settings'),        'done' => true],
    ['label' => 'Add fields',        'href' => url('fields'),          'done' => $stats['fields'] > 0],
    ['label' => 'Add crops',         'href' => url('crops'),           'done' => ($stats['active_crops'] + $stats['harvested_crops']) > 0],
    ['label' => 'Record activity',   'href' => url('activities'),      'done' => $recentActivities !== []],
    ['label' => 'Export report',     'href' => url('records'),         'done' => false],
];
$onboardingDone = count(array_filter($onboarding, static fn ($s) => $s['done']));

$roleActions = [
    'owner'        => [['label' => 'Review profitability', 'href' => url('reports'),   'icon' => 'bar-chart'], ['label' => 'Invite team', 'href' => url('team'), 'icon' => 'users']],
    'manager'      => [['label' => 'Plan field work',      'href' => url('activities'),'icon' => 'leaf'],      ['label' => 'Check active crops', 'href' => url('crops'), 'icon' => 'sprout']],
    'accountant'   => [['label' => 'Add transaction',      'href' => url('finance'),   'icon' => 'wallet'],    ['label' => 'Export records', 'href' => url('records'), 'icon' => 'file-text']],
    'agronomist'   => [['label' => 'Log an activity',      'href' => url('activities'),'icon' => 'leaf'],      ['label' => 'Open farm map', 'href' => url('map'), 'icon' => 'map']],
    'field_worker' => [['label' => 'Log an activity',      'href' => url('activities'),'icon' => 'leaf'],      ['label' => 'View fields', 'href' => url('fields'), 'icon' => 'map']],
    'viewer'       => [['label' => 'View reports',         'href' => url('reports'),   'icon' => 'bar-chart'], ['label' => 'View records', 'href' => url('records'), 'icon' => 'file-text']],
][$ctx->authz->role] ?? [];
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Good <?= e($greeting) ?><?= $firstName !== '' ? ', ' . e($firstName) : '' ?></h1>
        <p class="lede">Here's what's happening at <strong><?= e($ctx->farmName()) ?></strong></p>
    </div>
    <div class="text-right">
        <div class="small" style="font-weight:700;color:var(--text-soft)"><?= e(date('l, j F')) ?></div>
        <div class="small muted"><?= e(date('H:i')) ?></div>
    </div>
</div>

<?php if ($ctx->isReadOnly()): ?>
    <div class="alert warning mb-24">
        <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
        <div><strong>Read-only.</strong> Your subscription has lapsed — records stay visible, editing resumes on renewal.</div>
    </div>
<?php endif; ?>

<?php if ($onboardingDone < count($onboarding)): ?>
    <div class="card mb-24" style="background:linear-gradient(135deg,#EFF6FF,#F8FAFC);border-color:#BFDBFE">
        <div class="card-body">
            <div class="spread mb-16">
                <div>
                    <div class="h3" style="color:#075985">Getting started</div>
                    <p class="small mt-8" style="color:#0369A1">Set up the minimum record trail lenders, buyers, and managers expect.</p>
                </div>
                <span class="badge blue"><?= e((string) $onboardingDone) ?>/<?= e((string) count($onboarding)) ?></span>
            </div>
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
                <?php foreach ($onboarding as $step): ?>
                    <a href="<?= e($step['href']) ?>" class="row" style="background:<?= $step['done'] ? 'rgba(255,255,255,.75)' : '#fff' ?>;border:1px solid #BFDBFE;border-radius:16px;padding:10px 14px">
                        <span style="width:32px;height:32px;border-radius:10px;background:<?= $step['done'] ? 'var(--teal-pale)' : '#E0F2FE' ?>;color:<?= $step['done'] ? 'var(--teal)' : '#0284C7' ?>;display:grid;place-items:center;flex:none">
                            <?= $this->partial('partials/icon', ['name' => $step['done'] ? 'check-circle' : 'plus', 'class' => 'ico']) ?>
                        </span>
                        <span class="small" style="font-weight:700;color:var(--text)"><?= e($step['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php elseif (!$hasAnyRecords): ?>
    <div class="card mb-24" style="background:#EFF6FF;border-color:#BFDBFE">
        <div class="card-body spread">
            <div>
                <div class="h3" style="color:#1E3A8A">This active farm has no records yet</div>
                <p class="small mt-8" style="color:#1D4ED8">You are viewing <?= e($ctx->farmName()) ?>. If your data is under another farm, use the farm switcher in the sidebar.</p>
            </div>
            <a href="<?= e(url('settings')) ?>" class="btn secondary sm">Check farms</a>
        </div>
    </div>
<?php endif; ?>

<?php
$alerts = array_filter([
    ($stats['active_crops'] > 0 && $recentActivities === []) ? 'Missing activity records for active crops.' : null,
    ($stats['net'] < 0) ? 'Low margin detected from current costs and income.' : null,
    ($stats['total_activity_cost'] > max($stats['income'] * 0.7, 1)) ? 'Input and activity costs are high compared with recorded income.' : null,
]);
?>
<?php if ($alerts !== []): ?>
    <div class="grid cols-3 mb-24">
        <?php foreach ($alerts as $alert): ?>
            <div class="row" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:16px;padding:12px 14px">
                <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
                <span class="small" style="font-weight:700;color:#1E3A8A"><?= e($alert) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($ctx->can('crops.manage') && !$ctx->isReadOnly()): ?>
    <div class="row mb-16" style="justify-content:flex-end">
        <a class="btn secondary" href="<?= e(url('fields/create')) ?>">Add field</a>
        <a class="btn" href="<?= e(url('crops/create')) ?>">Add planting</a>
    </div>
<?php endif; ?>

<div class="grid cols-4">
    <div class="stat">
        <div class="label">Total fields</div>
        <div class="value"><?= e((string) $stats['fields']) ?></div>
        <div class="delta muted"><?= e(number_format((float) $stats['total_area'], 1)) ?> ha total</div>
    </div>
    <div class="stat">
        <div class="label">Active crops</div>
        <div class="value"><?= e((string) $stats['active_crops']) ?></div>
        <div class="delta muted"><?= e((string) $stats['harvested_crops']) ?> harvested</div>
    </div>
    <div class="stat">
        <div class="label">Total yield</div>
        <div class="value"><?= e(number_format((float) $stats['total_yield_kg'])) ?> kg</div>
        <div class="delta muted">All harvests</div>
    </div>
    <div class="stat">
        <div class="label">Net income</div>
        <div class="value" style="color:<?= $netPositive ? 'var(--green-text)' : 'var(--red-text)' ?>"><?= e(Money::format(abs((float) $stats['net']))) ?></div>
        <div class="delta <?= $netPositive ? 'up' : 'down' ?>"><?= $netPositive ? 'Profitable' : 'Running at loss' ?></div>
    </div>
</div>

<div class="grid cols-4 mt-16 mb-24">
    <?php
    $secondary = [
        ['label' => 'Income',           'value' => Money::format((float) $stats['income']),              'color' => 'var(--green-text)'],
        ['label' => 'Activity costs',   'value' => Money::format((float) $stats['total_activity_cost']),  'color' => 'var(--red-text)'],
        ['label' => 'Overhead',         'value' => Money::format((float) $stats['total_overhead']),       'color' => 'var(--sky-600)'],
        ['label' => 'Inventory items',  'value' => (string) $stats['total_inventory'],                     'color' => 'var(--blue)'],
    ];
    ?>
    <?php foreach ($secondary as $s): ?>
        <div class="spread" style="background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:12px 16px">
            <span class="small" style="font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint)"><?= e($s['label']) ?></span>
            <span style="font-weight:800;font-size:1rem;color:<?= $s['color'] ?>"><?= e($s['value']) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid cols-2 mb-24">
    <div class="card"><div class="card-body">
        <div class="spread mb-16">
            <span class="h3">Season profitability</span>
            <?= $this->partial('partials/icon', ['name' => 'bar-chart', 'class' => 'ico']) ?>
        </div>
        <?php if ($seasonProfit === []): ?>
            <p class="small muted">Add seasonal crop records to see profit trends.</p>
        <?php else: ?>
            <div class="stack">
                <?php
                $maxSeason = max(array_map(static fn ($s) => abs($s['netRevenue']), $seasonProfit) ?: [1]) ?: 1;
                foreach (array_slice($seasonProfit, 0, 4) as $s):
                    $width = max(6, (int) round(abs($s['netRevenue']) / $maxSeason * 100));
                    $positive = $s['netRevenue'] >= 0;
                ?>
                    <div>
                        <div class="spread small" style="font-weight:700;color:var(--text-soft);margin-bottom:4px">
                            <span><?= e($s['name']) ?></span>
                            <span style="color:<?= $positive ? 'var(--teal)' : 'var(--red)' ?>"><?= $positive ? '+' : '-' ?><?= e(Money::format(abs($s['netRevenue']))) ?></span>
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
        <div class="spread mb-16">
            <span class="h3">Crop area mix</span>
            <?= $this->partial('partials/icon', ['name' => 'sprout', 'class' => 'ico']) ?>
        </div>
        <?php if ($cropSummary === []): ?>
            <p class="small muted">Add crops to see area distribution.</p>
        <?php else: ?>
            <div class="stack">
                <?php
                $maxArea = max(array_map(static fn ($c) => $c['totalArea'], $cropSummary) ?: [1]) ?: 1;
                foreach (array_slice($cropSummary, 0, 5) as $c):
                    $width = max(6, (int) round($c['totalArea'] / $maxArea * 100));
                ?>
                    <div>
                        <div class="spread small" style="font-weight:700;color:var(--text-soft);margin-bottom:4px">
                            <span><?= e($c['name']) ?></span>
                            <span><?= e(number_format($c['totalArea'], 1)) ?> ha</span>
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

<?php if ($roleActions !== []): ?>
    <div class="grid cols-2 mb-24">
        <?php foreach ($roleActions as $a): ?>
            <a href="<?= e($a['href']) ?>" class="row" style="background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:12px 16px">
                <span style="width:40px;height:40px;border-radius:16px;background:var(--blue-050);color:#1E40AF;display:grid;place-items:center;flex:none">
                    <?= $this->partial('partials/icon', ['name' => $a['icon'], 'class' => 'ico']) ?>
                </span>
                <div>
                    <div class="small" style="font-weight:800;color:var(--text)"><?= e($a['label']) ?></div>
                    <div class="small muted" style="text-transform:capitalize"><?= e(str_replace('_', ' ', $ctx->authz->role)) ?> action</div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid cols-3">
    <div class="card"><div class="card-body">
        <div class="spread mb-16">
            <span class="h3">Land utilisation</span>
            <a href="<?= e(url('fields')) ?>" class="small" style="font-weight:700">View</a>
        </div>
        <?php if ($fieldLandUse === []): ?>
            <p class="small muted">No fields added yet</p>
        <?php else: ?>
            <div class="stack">
                <?php foreach ($fieldLandUse as $f):
                    $pct = $f['cultivatableArea'] > 0 ? min(100, round($f['allocated'] / $f['cultivatableArea'] * 100)) : 0;
                    $barColor = $pct > 90 ? 'var(--red)' : ($pct > 70 ? 'var(--amber)' : 'var(--teal)');
                ?>
                    <div>
                        <div class="spread small" style="margin-bottom:4px">
                            <span style="font-weight:700;color:var(--text)"><?= e($f['name']) ?></span>
                            <span class="muted" style="font-weight:600"><?= e(number_format($f['allocated'], 1)) ?>/<?= e(number_format($f['cultivatableArea'], 1)) ?> ha</span>
                        </div>
                        <div style="height:8px;background:var(--surface-3);border-radius:999px;overflow:hidden">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $barColor ?>;border-radius:999px"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="spread mb-16">
            <span class="h3">Crops</span>
            <a href="<?= e(url('crops')) ?>" class="small" style="font-weight:700">View</a>
        </div>
        <?php if ($cropSummary === []): ?>
            <p class="small muted">No crops yet</p>
        <?php else: ?>
            <div class="stack">
                <?php foreach (array_slice($cropSummary, 0, 5) as $c):
                    $harvestedN = count(array_filter($c['statuses'], static fn ($s) => $s === 'Harvested'));
                ?>
                    <div class="spread" style="padding:8px 0;border-bottom:1px solid var(--line)">
                        <div class="row" style="gap:8px">
                            <span style="width:32px;height:32px;border-radius:12px;background:var(--surface-2);color:var(--teal);display:grid;place-items:center;flex:none">
                                <?= $this->partial('partials/icon', ['name' => 'sprout', 'class' => 'ico']) ?>
                            </span>
                            <div>
                                <div class="small" style="font-weight:700;color:var(--text)"><?= e($c['name']) ?></div>
                                <div style="font-size:.7rem;color:var(--text-faint)"><?= e(number_format($c['totalArea'], 1)) ?> ha</div>
                            </div>
                        </div>
                        <div class="row" style="gap:6px">
                            <?php if (in_array('Active', $c['statuses'], true)): ?><span class="badge green">Active</span><?php endif; ?>
                            <?php if ($harvestedN > 0): ?><span class="badge blue"><?= $harvestedN ?> harvested</span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="spread mb-16">
            <span class="h3">Recent activities</span>
            <a href="<?= e(url('activities')) ?>" class="small" style="font-weight:700">View</a>
        </div>
        <?php if ($recentActivities === []): ?>
            <p class="small muted">No activities logged yet</p>
        <?php else: ?>
            <div class="stack">
                <?php foreach ($recentActivities as $a): ?>
                    <div class="row" style="gap:10px;padding:8px 0;border-bottom:1px solid var(--line);align-items:flex-start">
                        <span style="width:30px;height:30px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;flex:none">
                            <?= $this->partial('partials/icon', ['name' => 'leaf', 'class' => 'ico']) ?>
                        </span>
                        <div style="flex:1;min-width:0">
                            <div class="small" style="font-weight:700;color:var(--text)"><?= e((string) $a['activity_type']) ?></div>
                            <div style="font-size:.7rem;color:var(--text-faint)" class="nowrap"><?= e((string) $a['field_name']) ?><?= $a['crop_name'] ? ' — ' . e((string) $a['crop_name']) : '' ?></div>
                        </div>
                        <div style="font-size:.7rem;font-weight:700;color:var(--text-hint)" class="nowrap"><?= e(Dates::forDisplay((string) $a['date'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div></div>
</div>

<?php if ($upcoming !== []): ?>
    <div class="card mt-24"><div class="card-head"><h2 class="h2">Harvests due in 30 days</h2></div>
        <div class="card-body">
            <ul style="list-style:none;padding:0;margin:0">
                <?php foreach ($upcoming as $u): ?>
                    <li class="spread" style="padding:9px 0;border-bottom:1px solid var(--line)">
                        <span>
                            <a href="<?= e(url('crops/' . rawurlencode((string) $u['id']))) ?>"><strong><?= e((string) $u['crop_name']) ?></strong></a>
                            <span class="muted small">· <?= e((string) $u['field_name']) ?></span>
                        </span>
                        <span class="badge amber"><?= e(Dates::forDisplay((string) $u['expected_harvest_date'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
