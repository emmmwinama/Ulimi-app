<?php $this->layout('layouts/plain'); $title = 'Page not found'; ?>
<?php $this->start('content'); ?>
    <p class="eyebrow">Error 404</p>
    <h1 class="h1 mt-8">We can’t find that page</h1>
    <p class="muted mt-8">The link may be broken or the page may have moved.</p>
    <p class="mt-24"><a class="btn" href="<?= e(url('/')) ?>">Back to safety</a></p>
<?php $this->stop(); ?>
