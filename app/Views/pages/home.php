<?php
/**
 * @var array<string,string> $content
 * @var array<int,array<string,mixed>> $features
 * @var array<int,array<string,mixed>> $testimonials
 * @var array<int,array<string,mixed>> $tiers
 */
$this->layout('layouts/public');
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<section class="hero">
    <div class="container">
        <p class="eyebrow"><?= e($content['hero_eyebrow'] ?? 'Farm records & evidence') ?></p>
        <h1 class="h1"><?= e($content['hero_title'] ?? 'The record vault your farm can prove.') ?></h1>
        <p class="lede"><?= e($content['hero_subtitle'] ?? '') ?></p>
        <div class="cta">
            <a class="btn" href="<?= e(url('register')) ?>">Start free</a>
            <a class="btn secondary" href="<?= e(url('login')) ?>">Sign in</a>
        </div>
    </div>
</section>

<?php if ($features !== []): ?>
<section class="feature-grid">
    <div class="container grid cols-3">
        <?php foreach ($features as $f): ?>
            <div class="feature">
                <div class="ico"><?= $this->partial('partials/icon', ['name' => (string) $f['icon'], 'class' => 'ico']) ?></div>
                <h3 class="h3"><?= e((string) $f['title']) ?></h3>
                <p class="muted small mt-8px"><?= e((string) $f['description']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($tiers !== []): ?>
<section class="container" style="padding:8px 0 64px">
    <div class="text-center mb-24px">
        <p class="eyebrow">Pricing</p>
        <h2 class="h2" style="font-size:1.6rem">Plans that grow with your farm</h2>
    </div>
    <div class="grid cols-3">
        <?php foreach ($tiers as $t): ?>
            <div class="card" style="<?= (int) $t['is_featured'] === 1 ? 'border-color:var(--blue);box-shadow:0 0 0 2px var(--blue-050)' : '' ?>">
                <div class="card-body text-center">
                    <?php if ((int) $t['is_featured'] === 1): ?><span class="badge blue mb-8px">Popular</span><?php endif; ?>
                    <div class="h3"><?= e((string) $t['name']) ?></div>
                    <div style="font-size:1.8rem;font-weight:800;color:var(--navy);margin:10px 0">
                        <?= (float) $t['price_monthly'] > 0 ? e(Money::format((float) $t['price_monthly'], (string) $t['currency'])) . '<span class="small muted">/mo</span>' : 'Free' ?>
                    </div>
                    <p class="small muted mb-16px"><?= e((string) $t['description']) ?></p>
                    <a class="btn <?= (int) $t['is_featured'] === 1 ? '' : 'secondary' ?> block" href="<?= e(url('register')) ?>">Get started</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($testimonials !== []): ?>
<section class="container" style="padding:8px 0 64px">
    <div class="grid cols-2">
        <?php foreach ($testimonials as $t): ?>
            <div class="card"><div class="card-body">
                <p style="font-style:italic">&ldquo;<?= e((string) $t['quote']) ?>&rdquo;</p>
                <div class="row mt-16px" style="gap:10px">
                    <span class="badge blue" style="width:36px;height:36px;border-radius:999px;display:grid;place-items:center;font-weight:800"><?= e((string) $t['initials']) ?></span>
                    <div><strong><?= e((string) $t['name']) ?></strong><div class="small muted"><?= e((string) $t['role']) ?></div></div>
                </div>
            </div></div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container" style="padding:8px 0 72px;max-width:560px">
    <div class="card"><div class="card-body">
        <h2 class="h2 mb-16px">Get in touch</h2>
        <?= $this->partial('partials/contact-form') ?>
    </div></div>
</section>
<?php $this->stop(); ?>
