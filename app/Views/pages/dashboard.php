<?php
/**
 * @var App\Core\FarmContext $ctx
 * @var array<string,mixed>|null $subscription
 * @var array{fields:int,total_area:float,active_crops:int,team:int,seasons:int} $stats
 * @var array<int,array<string,mixed>> $upcoming
 */
$this->layout('layouts/app');
use App\Support\Dates;

$sub = $subscription;
[$statusText, $statusCls] = [
    'trial'     => ['Trial', 'blue'],
    'active'    => ['Active', 'green'],
    'past_due'  => ['Past due — read only', 'amber'],
    'expired'   => ['Expired — read only', 'red'],
    'suspended' => ['Suspended', 'red'],
][$ctx->subscriptionStatus()] ?? ['—', ''];
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1"><?= e($ctx->farmName()) ?></h1>
        <p class="lede"><?= e((string) ($ctx->farm['location'] ?? '')) ?> ·
            you are <strong><?= e(str_replace('_', ' ', $ctx->authz->role)) ?></strong></p>
    </div>
    <?php if ($ctx->can('crops.manage') && !$ctx->isReadOnly()): ?>
        <div class="row">
            <a class="btn secondary" href="<?= e(url('fields/create')) ?>">Add field</a>
            <a class="btn" href="<?= e(url('crops/create')) ?>">Add planting</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($ctx->isReadOnly()): ?>
    <div class="alert warning mb-24">
        <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
        <div><strong>Read-only.</strong> Your subscription has lapsed — records stay visible, editing resumes on renewal.</div>
    </div>
<?php endif; ?>

<div class="grid cols-4">
    <div class="stat">
        <div class="label">Fields</div>
        <div class="value"><?= e((string) $stats['fields']) ?></div>
        <div class="delta muted"><?= e(number_format($stats['total_area'], 1)) ?> ha total</div>
    </div>
    <div class="stat">
        <div class="label">Active plantings</div>
        <div class="value"><?= e((string) $stats['active_crops']) ?></div>
        <div class="delta muted"><?= e((string) $stats['seasons']) ?> season<?= $stats['seasons'] === 1 ? '' : 's' ?> on record</div>
    </div>
    <div class="stat">
        <div class="label">Team</div>
        <div class="value"><?= e((string) $stats['team']) ?></div>
        <div class="delta muted"><a href="<?= e(url('team')) ?>">Manage</a></div>
    </div>
    <div class="stat">
        <div class="label">Plan</div>
        <div class="value" style="font-size:1.05rem"><?= e((string) ($sub['tier_name'] ?? '—')) ?></div>
        <div class="delta"><span class="badge <?= e($statusCls) ?>"><?= e($statusText) ?></span></div>
    </div>
</div>

<div class="grid cols-2 mt-24">
    <div class="card">
        <div class="card-head"><h2 class="h2">Harvests due in 30 days</h2></div>
        <div class="card-body">
            <?php if ($upcoming === []): ?>
                <p class="muted">Nothing coming up. Add plantings with expected harvest dates to see them here.</p>
            <?php else: ?>
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
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Next steps</h2></div>
        <div class="card-body">
            <ul style="list-style:none;padding:0;margin:0" class="stack">
                <li class="row" style="gap:10px">
                    <?= $this->partial('partials/icon', ['name' => $stats['fields'] > 0 ? 'check-circle' : 'map', 'class' => 'ico']) ?>
                    <span><a href="<?= e(url('fields')) ?>">Record your fields</a> — <?= $stats['fields'] > 0 ? e((string) $stats['fields']) . ' recorded' : 'none yet' ?></span>
                </li>
                <li class="row" style="gap:10px">
                    <?= $this->partial('partials/icon', ['name' => $stats['active_crops'] > 0 ? 'check-circle' : 'sprout', 'class' => 'ico']) ?>
                    <span><a href="<?= e(url('crops')) ?>">Record what’s planted</a> — <?= $stats['active_crops'] > 0 ? e((string) $stats['active_crops']) . ' active' : 'none yet' ?></span>
                </li>
                <li class="row" style="gap:10px">
                    <?= $this->partial('partials/icon', ['name' => 'leaf', 'class' => 'ico']) ?>
                    <span class="muted">Activities, finance, inventory &amp; livestock — arriving next</span>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
