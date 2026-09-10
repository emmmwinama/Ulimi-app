<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1">Welcome back</h1>
<p class="sub">Sign in to your AgriVault account.</p>

<form method="post" action="<?= e(url('login')) ?>" class="stack">
    <?= csrf_field() ?>
    <?= $this->partial('partials/field', [
        'name' => 'email', 'label' => 'Email', 'type' => 'email',
        'required' => true, 'autocomplete' => 'email',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'password', 'label' => 'Password', 'type' => 'password',
        'required' => true, 'autocomplete' => 'current-password',
    ]) ?>
    <div class="spread" style="margin-top:6px">
        <span></span>
        <a href="<?= e(url('forgot-password')) ?>" class="small">Forgot password?</a>
    </div>
    <button type="submit" class="btn block">Sign in</button>
</form>

<p class="altline">New to AgriVault? <a href="<?= e(url('register')) ?>">Create an account</a></p>
<?php $this->stop(); ?>
