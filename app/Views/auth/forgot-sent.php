<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1">Check your email</h1>
<p class="sub">
    If an active account exists for <strong><?= e($email ?? '') ?></strong>, a password-reset link
    is on its way. The link expires in one hour.
</p>
<p class="altline"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
<?php $this->stop(); ?>
