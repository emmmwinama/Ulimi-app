<?php
/**
 * @var App\Core\FarmContext $ctx
 * @var int $teamCount
 * @var array<string,mixed>|null $subscription
 */
$this->layout('layouts/app');
use App\Support\Dates;

$sub = $subscription;
$statusLabel = [
    'trial'     => ['Trial', 'blue'],
    'active'    => ['Active', 'green'],
    'past_due'  => ['Past due — read only', 'amber'],
    'expired'   => ['Expired — read only', 'red'],
    'suspended' => ['Suspended', 'red'],
][$ctx->subscriptionStatus()] ?? ['Unknown', ''];
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1"><?= e($ctx->farmName()) ?></h1>
        <p class="lede">
            <?= e((string) ($ctx->farm['location'] ?? '')) ?> ·
            You are <strong><?= e(str_replace('_', ' ', $ctx->authz->role)) ?></strong> on this farm
        </p>
    </div>
</div>

<div class="grid cols-4">
    <div class="stat">
        <div class="label">Plan</div>
        <div class="value" style="font-size:1.1rem"><?= e((string) ($sub['tier_name'] ?? '—')) ?></div>
        <div class="delta"><span class="badge <?= e($statusLabel[1]) ?>"><?= e($statusLabel[0]) ?></span></div>
    </div>
    <div class="stat">
        <div class="label">Team members</div>
        <div class="value"><?= e((string) $teamCount) ?></div>
        <div class="delta muted">
            <?php $max = $sub !== null ? (int) $sub['max_team_members'] : 1; ?>
            <?= $max === -1 ? 'Unlimited' : 'of ' . e((string) $max) ?>
        </div>
    </div>
    <div class="stat">
        <div class="label">Trial ends</div>
        <div class="value" style="font-size:1.1rem">
            <?= e(Dates::forDisplay($sub['trial_ends_at'] ?? null)) ?>
        </div>
    </div>
    <div class="stat">
        <div class="label">Farm created</div>
        <div class="value" style="font-size:1.1rem">
            <?= e(Dates::forDisplay((string) ($ctx->farm['created_at'] ?? ''))) ?>
        </div>
    </div>
</div>

<?php if ($ctx->isReadOnly()): ?>
    <div class="alert warning mt-24">
        <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
        <div>
            <strong>This account is read-only.</strong>
            Your subscription has lapsed. Existing records stay visible; renew to resume editing.
        </div>
    </div>
<?php endif; ?>

<div class="card mt-24">
    <div class="card-head">
        <h2 class="h2">Getting your records in</h2>
    </div>
    <div class="card-body">
        <p class="muted">
            The record modules are being rolled out in order. Each one you see greyed in the
            sidebar becomes available as it lands.
        </p>
        <div class="grid cols-2 mt-16">
            <?php
            $modules = [
                ['sprout', 'Fields & Crops', 'Field records, soil, area; crop plantings, varieties, seasons and a stage-by-stage timeline.'],
                ['leaf', 'Activities & Labour', 'Log land prep, planting, spraying, weeding and harvest with labour, inputs and other costs.'],
                ['wallet', 'Finance', 'Income and expenses, overheads, and season-by-season profitability.'],
                ['boxes', 'Inventory', 'Harvested produce and input stock, with sales linked back to finance.'],
                ['cow', 'Livestock', 'Animal register, health, weight, production and sales.'],
                ['bar-chart', 'Reports & Evidence', 'Buyer packs, loan-readiness files, audit and insurance evidence — as PDF or CSV.'],
            ];
            foreach ($modules as [$icon, $t, $d]): ?>
                <div class="row" style="align-items:flex-start;gap:12px">
                    <span class="feature-ico" style="flex:none;width:34px;height:34px;border-radius:9px;background:var(--blue-050);color:var(--blue-600);display:grid;place-items:center">
                        <?= $this->partial('partials/icon', ['name' => $icon, 'class' => 'ico']) ?>
                    </span>
                    <div>
                        <div class="h3"><?= e($t) ?></div>
                        <p class="small muted mt-8"><?= e($d) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid cols-2 mt-24">
    <div class="card">
        <div class="card-body">
            <div class="h3">Invite your team</div>
            <p class="small muted mt-8">Add a manager, agronomist, accountant or field worker with the right permissions.</p>
            <p class="mt-16"><a class="btn secondary sm" href="<?= e(url('team')) ?>">Manage team</a></p>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="h3">Account &amp; security</div>
            <p class="small muted mt-8">Update your details and change your password.</p>
            <p class="mt-16"><a class="btn secondary sm" href="<?= e(url('account')) ?>">Account settings</a></p>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
