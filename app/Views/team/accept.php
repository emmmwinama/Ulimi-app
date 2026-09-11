<?php $this->layout('layouts/auth'); /** @var array<string,mixed>|null $invite */ ?>
<?php $this->start('content'); ?>
<?php if ($invite === null): ?>
    <h1 class="h1">Invitation not found</h1>
    <p class="sub">This invitation link is invalid or has expired. Ask the farm owner to send a new one.</p>
    <p class="altline"><a href="<?= e(url('login')) ?>">Go to sign in</a></p>
<?php else: ?>
    <h1 class="h1">Join <?= e($farmName ?? 'this farm') ?></h1>
    <p class="sub">
        You’ve been invited as
        <strong><?= e(ucwords(str_replace('_', ' ', (string) $invite['role']))) ?></strong>.
        The invitation was sent to <strong><?= e((string) $invite['invite_email']) ?></strong>.
    </p>

    <?php if ($authed ?? false): ?>
        <form method="post" action="<?= e(url('invite')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
            <button type="submit" class="btn block">Accept invitation</button>
        </form>
    <?php else: ?>
        <div class="alert info mt-8px">
            <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
            <div>Sign in (or create an account) with <strong><?= e((string) $invite['invite_email']) ?></strong> to accept.</div>
        </div>
        <div class="stack mt-16px">
            <a class="btn block" href="<?= e(url('login')) ?>">Sign in</a>
            <a class="btn secondary block" href="<?= e(url('register')) ?>">Create account</a>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php $this->stop(); ?>
