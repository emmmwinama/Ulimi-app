<?php $this->layout('layouts/auth'); /** @var bool $isFirst */ ?>
<?php $this->start('content'); ?>
<h1 class="h1"><?= ($isFirst ?? true) ? 'Set up your farm' : 'Add a farm' ?></h1>
<p class="sub">
    <?= ($isFirst ?? true)
        ? 'Tell us the basics. You can refine everything later in settings.'
        : 'Create another farm under your account.' ?>
</p>

<form method="post" action="<?= e(url('onboarding/farm')) ?>" class="stack">
    <?= csrf_field() ?>
    <?= $this->partial('partials/field', [
        'name' => 'name', 'label' => 'Farm name', 'required' => true,
        'placeholder' => 'e.g. Emmanuel Mwinama’s Farm',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'location', 'label' => 'Location / district', 'required' => true,
        'placeholder' => 'e.g. Mchinji',
    ]) ?>
    <?= $this->partial('partials/field', [
        'name' => 'owner_name', 'label' => 'Owner name (optional)',
    ]) ?>
    <div class="grid cols-2">
        <?= $this->partial('partials/field', [
            'name' => 'location_lat', 'label' => 'Latitude (optional)', 'type' => 'number',
            'step' => 'any', 'inputmode' => 'decimal', 'placeholder' => '-13.80',
        ]) ?>
        <?= $this->partial('partials/field', [
            'name' => 'location_lng', 'label' => 'Longitude (optional)', 'type' => 'number',
            'step' => 'any', 'inputmode' => 'decimal', 'placeholder' => '32.88',
        ]) ?>
    </div>
    <button type="submit" class="btn block"><?= ($isFirst ?? true) ? 'Create farm & continue' : 'Create farm' ?></button>
</form>
<?php $this->stop(); ?>
