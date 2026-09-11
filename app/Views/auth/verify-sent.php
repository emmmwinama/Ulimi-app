<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1">Confirm your email</h1>
<p class="sub">
    If <strong><?= e($email ?? '') ?></strong> is not already registered, we’ve sent an
    activation link to it. Open the email and click the link to finish setting up your account.
</p>
<div class="alert info mt-16px">
    <?= $this->partial('partials/icon', ['name' => 'mail', 'class' => 'ico']) ?>
    <div>The link is valid for 24 hours. Check your spam folder if it doesn’t arrive within a few minutes.</div>
</div>
<p class="altline"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
<?php $this->stop(); ?>
