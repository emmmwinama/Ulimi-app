<?php $this->layout('layouts/plain'); $title = 'Method not allowed'; ?>
<?php $this->start('content'); ?>
    <p class="eyebrow">Error 405</p>
    <h1 class="h1 mt-8">That action isn’t available here</h1>
    <p class="muted mt-8">The request method is not supported for this URL.</p>
    <p class="mt-24"><a class="btn secondary" href="<?= e(url('/')) ?>">Go home</a></p>
<?php $this->stop(); ?>
