<?php $this->layout('layouts/plain'); $title = 'Not allowed'; $message = $message ?? 'You do not have permission to view this page.'; ?>
<?php $this->start('content'); ?>
    <p class="eyebrow">Error 403</p>
    <h1 class="h1 mt-8px">Access denied</h1>
    <p class="muted mt-8px"><?= e($message) ?></p>
    <p class="mt-24px"><a class="btn secondary" href="<?= e(url('dashboard')) ?>">Return to dashboard</a></p>
<?php $this->stop(); ?>
