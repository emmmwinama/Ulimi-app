<?php $this->layout('layouts/plain'); $title = 'Too many attempts'; $minutes = $minutes ?? 15; ?>
<?php $this->start('content'); ?>
    <p class="eyebrow">Error 429</p>
    <h1 class="h1 mt-8">Too many attempts</h1>
    <p class="muted mt-8">You’ve made too many requests. Please wait about <?= e((string) $minutes) ?> minute<?= $minutes === 1 ? '' : 's' ?> and try again.</p>
    <p class="mt-24"><a class="btn secondary" href="<?= e(url('/')) ?>">Go home</a></p>
<?php $this->stop(); ?>
