<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1">Activate your account</h1>
<p class="sub">Confirm below to activate your AgriVault account and sign in.</p>
<form method="post" action="<?= e(url('activate')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
    <button type="submit" class="btn block">Activate &amp; continue</button>
</form>
<p class="altline"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
<?php $this->stop(); ?>
