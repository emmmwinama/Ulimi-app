<?php
/** Auth layout: split brand panel + form. @var string $title */
$title = $title ?? 'Sign in';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> — AgriVault</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#form">Skip to form</a>
<div class="auth">
    <aside class="brandside">
        <div class="pitch-wrap">
            <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield-check', 'class' => 'ico']) ?></span>
            <p class="pitch">The record vault your farm can prove.</p>
            <ul>
                <li><?= $this->partial('partials/icon', ['name' => 'check', 'class' => 'ico']) ?> Field, crop, activity &amp; finance records in one place</li>
                <li><?= $this->partial('partials/icon', ['name' => 'check', 'class' => 'ico']) ?> Buyer-, lender- and audit-ready evidence packs</li>
                <li><?= $this->partial('partials/icon', ['name' => 'check', 'class' => 'ico']) ?> Team access with role-based permissions</li>
            </ul>
        </div>
        <p class="small" style="color:rgba(255,255,255,.45);text-align:center">&copy; <?= e(date('Y')) ?> AgriVault</p>
    </aside>
    <main class="formside" id="form">
        <div class="formcard">
            <div class="form-brand">
                <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield-check', 'class' => 'ico']) ?></span>
                AgriVault
            </div>
            <?= $this->yieldContent() ?>
        </div>
    </main>
</div>
<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
