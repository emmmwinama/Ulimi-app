<?php
/**
 * @var string $heading
 * @var string $body   admin-authored HTML from cms_pages — trusted, not user input
 */
$this->layout('layouts/public');
$heading = $heading ?? 'AgriVault';
?>
<?php $this->start('content'); ?>
<section class="container" style="padding:56px 0; max-width:760px">
    <h1 class="h1"><?= e($heading) ?></h1>
    <div class="mt-16" style="line-height:1.7">
        <?= $body ?? '<p class="muted">Content coming soon.</p>' ?>
    </div>
</section>
<?php $this->stop(); ?>
