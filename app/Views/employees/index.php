<?php
/**
 * @var array<int,array<string,mixed>> $employees
 * @var bool $canManage @var bool $payroll
 */
$this->layout('layouts/app');
use App\Support\Money;
use App\Support\Str;

$active = array_filter($employees, static fn ($e) => (int) $e['is_active'] === 1);
$monthlyPayroll = array_sum(array_map(
    static fn ($e) => (string) $e['pay_rate_unit'] === 'month' ? (float) $e['pay_rate'] : 0.0,
    $employees,
));
$avatarColors = ['#1A3D1F', '#0F766E', '#1E3A8A', '#3C3489', '#075985', '#9A3412'];
$roleBadge = [
    'Manager'    => ['bg' => '#F5F3FF', 'fg' => '#3C3489'],
    'Supervisor' => ['bg' => 'var(--blue-050)', 'fg' => '#1E3A8A'],
];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Employees</h1>
        <p class="lede"><?= count($active) ?> active · <?= count($employees) ?> total<?= $monthlyPayroll > 0 ? ' · ' . e(Money::format($monthlyPayroll)) . '/month payroll' : '' ?></p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="#add-employee">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add employee
        </a>
    <?php endif; ?>
</div>

<?php if (!$payroll): ?>
    <div class="alert info mb-16px">
        <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
        <div>Your plan doesn’t include payroll costing — you can keep the roster, but labour costs aren’t totalled automatically.</div>
    </div>
<?php endif; ?>

<?php if ($employees !== []): ?>
    <div class="grid cols-3 mb-16px">
        <div class="stat"><div class="label">Active staff</div><div class="value" style="font-size:1.4rem;color:var(--teal)"><?= count($active) ?></div></div>
        <div class="stat"><div class="label">Total staff</div><div class="value" style="font-size:1.4rem"><?= count($employees) ?></div></div>
        <div class="stat"><div class="label">Monthly payroll</div><div class="value" style="font-size:1.4rem;color:var(--blue)"><?= $monthlyPayroll > 0 ? e(Money::format($monthlyPayroll)) : '—' ?></div></div>
    </div>
<?php endif; ?>

<?php if ($employees === []): ?>
    <div class="empty">
        <span class="icon-box lg muted" style="margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'users', 'class' => 'ico']) ?>
        </span>
        <div class="h3">No employees</div>
        <p>Add your workers and their pay rates so activity labour can be costed.</p>
        <?php if ($canManage): ?><p class="mt-16px"><a class="btn" href="#add-employee">Add an employee</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid cols-3">
        <?php foreach ($employees as $idx => $e):
            $badge = $roleBadge[$e['role']] ?? ['bg' => 'var(--surface-2)', 'fg' => 'var(--text-soft)'];
            $avatarColor = $avatarColors[$idx % count($avatarColors)];
        ?>
            <div class="card"><div class="card-body">
                <div class="spread" style="align-items:flex-start;margin-bottom:12px">
                    <div class="row" style="gap:10px">
                        <span style="width:44px;height:44px;border-radius:12px;background:<?= $avatarColor ?>;color:#fff;display:grid;place-items:center;font-weight:900;font-size:.85rem;flex:none">
                            <?= e(Str::initials((string) $e['name'])) ?>
                        </span>
                        <div>
                            <p style="font-size:.875rem;font-weight:800;color:var(--text)"><?= e((string) $e['name']) ?></p>
                            <span class="badge" style="background:<?= $badge['bg'] ?>;color:<?= $badge['fg'] ?>;font-size:.625rem;margin-top:2px"><?= e((string) $e['role']) ?></span>
                        </div>
                    </div>
                    <?php if ($canManage): ?>
                        <div class="row" style="gap:4px">
                            <a href="#edit-employee-<?= e((string) $e['id']) ?>" title="Edit"
                               class="icon-box sm muted">
                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                            </a>
                            <form method="post" action="<?= e(url('employees/' . rawurlencode((string) $e['id']) . '/delete')) ?>" onsubmit="return confirm('Remove this employee?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Remove"
                                        class="icon-box sm red" style="border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mini-stat" style="margin-bottom:10px">
                    <p class="label">Pay rate</p>
                    <p style="font-size:.8rem;font-weight:800;color:var(--text)"><?= e(Money::format((float) $e['pay_rate'])) ?> <span class="small muted">/ <?= e((string) $e['pay_rate_unit']) ?></span></p>
                </div>
                <div class="spread">
                    <span class="small muted"><?= e((string) ($e['phone'] ?: 'No phone on file')) ?></span>
                    <span class="badge <?= (int) $e['is_active'] === 1 ? 'green' : '' ?>"><?= (int) $e['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                </div>
            </div></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage): ?>
    <div class="slide-over" id="add-employee">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Add employee</h2>
                    <p class="small muted mt-8px">Add a worker and their pay rate</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('employees')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/employees/employee', ['employee' => null]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add employee</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($employees as $e): ?>
        <div class="slide-over" id="edit-employee-<?= e((string) $e['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit employee</h2>
                        <p class="small muted mt-8px"><?= e((string) $e['name']) ?></p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('employees/' . rawurlencode((string) $e['id']))) ?>" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/employees/employee', ['employee' => $e]) ?>
                    </div>
                    <div class="panel-foot">
                        <a href="#" class="btn ghost block">Cancel</a>
                        <button type="submit" class="btn block">Save</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php $this->stop(); ?>
