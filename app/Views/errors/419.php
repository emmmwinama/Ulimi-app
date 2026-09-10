<?php $this->layout('layouts/plain'); $title = 'Session expired'; ?>
<?php $this->start('content'); ?>
    <p class="eyebrow">Error 419</p>
    <h1 class="h1 mt-8">Your session expired</h1>
    <p class="muted mt-8">For your security the form token is no longer valid. Go back, reload the page, and try again.</p>
    <p class="mt-24"><a class="btn" href="javascript:history.back()">Go back</a></p>
<?php $this->stop(); ?>
