<?php $this->layout('layouts/auth'); ?>
<?php $this->start('content'); ?>
<h1 class="h1"><?= ($ok ?? false) ? 'Account activated' : 'Activation problem' ?></h1>
<p class="sub"><?= e($message ?? '') ?></p>
<p class="altline"><a href="<?= e(url('login')) ?>">Go to sign in</a></p>
<?php $this->stop(); ?>
