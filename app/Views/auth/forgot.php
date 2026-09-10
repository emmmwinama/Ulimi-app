<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1">Reset your password</h1>
<p class="sub">Enter your email and we’ll send a link to choose a new password.</p>
<form method="post" action="<?= e(url('forgot-password')) ?>" class="stack">
    <?= csrf_field() ?>
    <?= $this->partial('partials/field', [
        'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email',
    ]) ?>
    <button type="submit" class="btn block">Send reset link</button>
</form>
<p class="altline"><a href="<?= e(url('login')) ?>">Back to sign in</a></p>
<?php $this->stop(); ?>
