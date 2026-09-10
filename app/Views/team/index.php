<?php
/**
 * @var array<int,array<string,mixed>> $members
 * @var list<string> $roles
 * @var bool $canManage
 * @var bool $teamEnabled
 * @var int $maxMembers
 * @var int $activeCount
 * @var bool $atLimit
 */
$this->layout('layouts/app');
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Team</h1>
        <p class="lede">
            <?= e((string) $activeCount) ?> active
            <?= $maxMembers === -1 ? '' : ' · limit ' . e((string) $maxMembers) ?>
        </p>
    </div>
</div>

<?php if (!$teamEnabled): ?>
    <div class="alert info">
        <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
        <div>Team accounts aren’t included in your current plan. You can still see your own membership below.</div>
    </div>
<?php endif; ?>

<?php if ($canManage && $teamEnabled): ?>
    <div class="card mb-24">
        <div class="card-head"><h2 class="h2">Invite a team member</h2></div>
        <div class="card-body">
            <?php if ($atLimit): ?>
                <div class="alert warning">
                    <?= $this->partial('partials/icon', ['name' => 'alert', 'class' => 'ico']) ?>
                    <div>You’ve reached your plan’s team-member limit. Remove a member or upgrade to add more.</div>
                </div>
            <?php else: ?>
                <form method="post" action="<?= e(url('team/invite')) ?>" class="row wrap" style="align-items:flex-end;gap:12px">
                    <?= csrf_field() ?>
                    <div class="field flex-1" style="min-width:220px">
                        <label for="f_email">Email address</label>
                        <input class="input" type="email" id="f_email" name="email" required autocomplete="off"
                               value="<?= e((string) old('email')) ?>"
                               <?= error_for('email') ? 'aria-invalid="true"' : '' ?>>
                        <?php if ($er = error_for('email')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
                    </div>
                    <div class="field" style="min-width:180px">
                        <label for="f_role">Role</label>
                        <select class="select" id="f_role" name="role">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= e($r) ?>" <?= old('role') === $r ? 'selected' : '' ?>>
                                    <?= e(ucwords(str_replace('_', ' ', $r))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Send invite</button>
                </form>
                <p class="hint mt-8">The invitation link expires in 72 hours and can only be accepted from the address it was sent to.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= e((string) ($m['user_name'] ?? '—')) ?></td>
                <td class="muted"><?= e((string) ($m['user_email'] ?? $m['invite_email'] ?? '—')) ?></td>
                <td>
                    <?php if ($canManage && $m['role'] !== 'owner' && $m['status'] === 'active'): ?>
                        <form method="post" action="<?= e(url('team/' . rawurlencode((string) $m['id']) . '/role')) ?>" class="row" style="gap:8px" data-noguard>
                            <?= csrf_field() ?>
                            <select class="select" name="role" onchange="this.form.submit()" style="min-height:36px;padding:4px 8px">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= e($r) ?>" <?= $m['role'] === $r ? 'selected' : '' ?>>
                                        <?= e(ucwords(str_replace('_', ' ', $r))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button class="btn sm secondary" type="submit">Save</button></noscript>
                        </form>
                    <?php else: ?>
                        <span class="badge"><?= e(ucwords(str_replace('_', ' ', (string) $m['role']))) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $st = (string) $m['status']; ?>
                    <span class="badge <?= $st === 'active' ? 'green' : ($st === 'invited' ? 'amber' : 'red') ?>">
                        <?= e(ucfirst($st)) ?>
                    </span>
                </td>
                <?php if ($canManage): ?>
                    <td class="num">
                        <?php if ($m['role'] !== 'owner'): ?>
                            <form method="post" action="<?= e(url('team/' . rawurlencode((string) $m['id']) . '/remove')) ?>"
                                  onsubmit="return confirm('Remove this team member?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn sm ghost danger">Remove</button>
                            </form>
                        <?php else: ?>
                            <span class="muted small">—</span>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->stop(); ?>
