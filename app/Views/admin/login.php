<?php $this->layout('layouts/plain'); ?>
<?php $this->start('content'); ?>
<div style="text-align:left;max-width:380px;margin:0 auto">
    <p class="eyebrow">AgriVault</p>
    <h1 class="h1 mt-8px">Admin sign in</h1>
    <p class="muted mt-8px mb-24px">Back-office access only.</p>
    <form method="post" action="<?= e(url('admin/login')) ?>" class="stack">
        <?= csrf_field() ?>
        <?= $this->partial('partials/field', [
            'name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'username',
        ]) ?>
        <?= $this->partial('partials/field', [
            'name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password',
        ]) ?>
        <button type="submit" class="btn block">Sign in</button>
    </form>
</div>
<?php $this->stop(); ?>
