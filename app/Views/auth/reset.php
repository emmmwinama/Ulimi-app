<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1">Choose a new password</h1>
<p class="sub">Pick something you don’t use anywhere else.</p>
<form method="post" action="<?= e(url('reset-password')) ?>" class="stack">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
    <?= $this->partial('partials/field', [
        'name' => 'password', 'label' => 'New password', 'type' => 'password',
        'required' => true, 'autocomplete' => 'new-password', 'hint' => 'At least 10 characters.',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password',
        'required' => true, 'autocomplete' => 'new-password',
    ]) ?>
    <button type="submit" class="btn block">Change password</button>
</form>
<?php $this->stop(); ?>
