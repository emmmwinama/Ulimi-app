<?php $this->layout('layouts/public'); $heading = $heading ?? 'AgriVault'; ?>
<?php $this->start('content'); ?>
<section class="container" style="padding:56px 0; max-width:760px">
    <h1 class="h1"><?= e($heading) ?></h1>
    <p class="muted mt-16">
        This page will carry managed content once the CMS module is in place.
        For now, reach us at <a href="mailto:support@agrivault.local">support@agrivault.local</a>.
    </p>
</section>
<?php $this->stop(); ?>
