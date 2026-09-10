<?php
/** @var array<string,mixed> $user */
$this->layout('layouts/app');
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Account</h1>
        <p class="lede">Your personal details and sign-in security.</p>
    </div>
</div>

<div class="content-narrow stack" style="--stack-gap:24px">
    <div class="card">
        <div class="card-head"><h2 class="h2">Your details</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('account/profile')) ?>" class="stack">
                <?= csrf_field() ?>
                <?= $this->partial('partials/field', [
                    'name' => 'name', 'label' => 'Name', 'required' => true,
                    'value' => old('name') ?: (string) ($user['name'] ?? ''), 'autocomplete' => 'name',
                ]) ?>
                <?= $this->partial('partials/field', [
                    'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true,
                    'value' => old('email') ?: (string) ($user['email'] ?? ''), 'autocomplete' => 'email',
                ]) ?>
                <div><button type="submit" class="btn">Save details</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Change password</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(url('account/password')) ?>" class="stack">
                <?= csrf_field() ?>
                <?= $this->partial('partials/field', [
                    'name' => 'current_password', 'label' => 'Current password', 'type' => 'password',
                    'required' => true, 'autocomplete' => 'current-password',
                ]) ?>
                <?= $this->partial('partials/field', [
                    'name' => 'password', 'label' => 'New password', 'type' => 'password',
                    'required' => true, 'autocomplete' => 'new-password', 'hint' => 'At least 10 characters.',
                ]) ?>
                <?= $this->partial('partials/field', [
                    'name' => 'password_confirmation', 'label' => 'Confirm new password', 'type' => 'password',
                    'required' => true, 'autocomplete' => 'new-password',
                ]) ?>
                <div><button type="submit" class="btn">Update password</button></div>
                <p class="hint">Changing your password signs out every other device.</p>
            </form>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
