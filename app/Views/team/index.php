<?php
/**
 * @var array<int,array<string,mixed>> $members
 * @var list<string> $roles
 * @var bool $canManage
 * @var bool $teamEnabled
 * @var int $maxMembers
 * @var int $activeCount
 * @var bool $atLimit
 * @var array<string,array<string,bool>> $roleMatrix
 * @var list<string> $allRoles
 */
$this->layout('layouts/app');
use App\Support\Str;

$roleBadge = [
    'owner'      => ['bg' => '#F5F3FF', 'fg' => '#3C3489'],
    'manager'    => ['bg' => 'var(--blue-050)', 'fg' => '#1E3A8A'],
    'agronomist' => ['bg' => 'var(--green-050)', 'fg' => 'var(--green-text)'],
    'accountant' => ['bg' => '#F0F9FF', 'fg' => '#075985'],
    'field_worker' => ['bg' => 'var(--surface-2)', 'fg' => 'var(--text-soft)'],
    'viewer'     => ['bg' => 'var(--surface-2)', 'fg' => 'var(--text-soft)'],
];
$avatarColors = ['#1A3D1F', '#0F766E', '#1E3A8A', '#3C3489', '#075985'];
$roleLabel = static fn (string $r): string => ucwords(str_replace('_', ' ', $r));
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
    <div class="alert info mb-16px">
        <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
        <div>Team accounts aren’t included in your current plan. You can still see your own membership below.</div>
    </div>
<?php endif; ?>

<?php if ($canManage && $teamEnabled): ?>
    <div class="card mb-16px">
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
                                    <?= e($roleLabel($r)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Send invite</button>
                </form>
                <p class="hint mt-8px">The invitation link expires in 72 hours and can only be accepted from the address it was sent to.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card mb-16px">
    <div class="card-body" style="overflow-x:auto">
        <p class="eyebrow mb-16px">Role permissions overview</p>
        <table style="width:100%;border-collapse:collapse;font-size:.75rem">
            <thead>
                <tr style="border-bottom:1px solid var(--line)">
                    <th style="text-align:left;padding:0 12px 10px 0;font-size:.625rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint)">Area</th>
                    <?php foreach ($allRoles as $r): $b = $roleBadge[$r] ?? $roleBadge['viewer']; ?>
                        <th style="text-align:center;padding:0 8px 10px"><span class="badge" style="background:<?= $b['bg'] ?>;color:<?= $b['fg'] ?>;font-size:.6rem"><?= e($roleLabel($r)) ?></span></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roleMatrix as $resource => $byRole): ?>
                    <tr style="border-bottom:1px solid var(--line)">
                        <td style="padding:8px 12px 8px 0;font-weight:700;color:var(--text-soft);text-transform:capitalize"><?= e($resource) ?></td>
                        <?php foreach ($allRoles as $r): ?>
                            <td style="text-align:center;padding:8px">
                                <?php if ($byRole[$r] ?? false): ?>
                                    <span style="color:var(--green-text)"><?= $this->partial('partials/icon', ['name' => 'check', 'class' => 'ico ico-sm']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--line-strong)">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <?php foreach ($members as $idx => $m):
        $badge = $roleBadge[$m['role']] ?? $roleBadge['viewer'];
        $st = (string) $m['status'];
        $name = (string) ($m['user_name'] ?? $m['invite_email'] ?? '?');
    ?>
        <div class="row" style="gap:14px;padding:14px 20px;<?= $idx > 0 ? 'border-top:1px solid var(--line);' : '' ?>">
            <span style="width:40px;height:40px;border-radius:12px;background:<?= $avatarColors[$idx % count($avatarColors)] ?>;color:#fff;display:grid;place-items:center;font-weight:900;font-size:.75rem;flex:none">
                <?= e(Str::initials($name)) ?>
            </span>
            <div style="flex:1;min-width:0">
                <p style="font-size:.875rem;font-weight:800;color:var(--text)"><?= e((string) ($m['user_name'] ?? '—')) ?></p>
                <p class="small muted nowrap"><?= e((string) ($m['user_email'] ?? $m['invite_email'] ?? '—')) ?></p>
            </div>
            <?php if ($canManage && $m['role'] !== 'owner' && $m['status'] === 'active'): ?>
                <form method="post" action="<?= e(url('team/' . rawurlencode((string) $m['id']) . '/role')) ?>" data-noguard>
                    <?= csrf_field() ?>
                    <select class="select" name="role" onchange="this.form.submit()" style="min-height:32px;height:32px;padding:0 10px;font-size:.75rem;border-radius:999px;background:<?= $badge['bg'] ?>;color:<?= $badge['fg'] ?>;border:0">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= e($r) ?>" <?= $m['role'] === $r ? 'selected' : '' ?>><?= e($roleLabel($r)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else: ?>
                <span class="badge" style="background:<?= $badge['bg'] ?>;color:<?= $badge['fg'] ?>"><?= e($roleLabel((string) $m['role'])) ?></span>
            <?php endif; ?>
            <span class="badge <?= $st === 'active' ? 'green' : ($st === 'invited' ? 'amber' : 'red') ?>"><?= e(ucfirst($st)) ?></span>
            <?php if ($canManage && $m['role'] !== 'owner'): ?>
                <form method="post" action="<?= e(url('team/' . rawurlencode((string) $m['id']) . '/remove')) ?>" onsubmit="return confirm('Remove this team member?')">
                    <?= csrf_field() ?>
                    <button type="submit" title="Remove"
                            class="icon-box sm red" style="border:0;cursor:pointer">
                        <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php $this->stop(); ?>
