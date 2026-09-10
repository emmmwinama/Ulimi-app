<?php
/**
 * @var array<int,array<string,mixed>> $employees
 * @var bool $canManage @var bool $payroll
 */
$this->layout('layouts/app');
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Employees</h1>
        <p class="lede"><?= count(array_filter($employees, static fn ($e) => (int) $e['is_active'] === 1)) ?> active</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('employees/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add employee
        </a>
    <?php endif; ?>
</div>

<?php if (!$payroll): ?>
    <div class="alert info mb-16">
        <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
        <div>Your plan doesn’t include payroll costing — you can keep the roster, but labour costs aren’t totalled automatically.</div>
    </div>
<?php endif; ?>

<?php if ($employees === []): ?>
    <div class="empty">
        <div class="h3">No employees</div>
        <p>Add your workers and their pay rates so activity labour can be costed.</p>
        <?php if ($canManage): ?><p class="mt-16"><a class="btn" href="<?= e(url('employees/create')) ?>">Add an employee</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Name</th><th>Role</th><th class="num">Pay rate</th><th>Phone</th><th>Status</th><?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($employees as $e): ?>
                <tr>
                    <td><strong><?= e((string) $e['name']) ?></strong></td>
                    <td><?= e((string) $e['role']) ?></td>
                    <td class="num"><?= e(Money::format((float) $e['pay_rate'])) ?> / <?= e((string) $e['pay_rate_unit']) ?></td>
                    <td class="muted"><?= e((string) ($e['phone'] ?: '—')) ?></td>
                    <td><span class="badge <?= (int) $e['is_active'] === 1 ? 'green' : '' ?>"><?= (int) $e['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                    <?php if ($canManage): ?>
                        <td class="num nowrap">
                            <a class="btn sm ghost" href="<?= e(url('employees/' . rawurlencode((string) $e['id']) . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(url('employees/' . rawurlencode((string) $e['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Remove this employee?')">
                                <?= csrf_field() ?>
                                <button class="btn sm ghost danger" type="submit">Remove</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
