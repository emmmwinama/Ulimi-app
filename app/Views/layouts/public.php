<?php
/** Public / marketing layout. @var string $title */
use App\Core\Auth;

$title = $title ?? 'AgriVault';
$authed = Auth::check();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="AgriVault — the farm record vault for field teams, managers, lenders, buyers and auditors.">
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
</head>
<body>
<header class="pub-header">
    <div class="container spread" style="width:100%">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield-check', 'class' => 'ico']) ?></span>
            AgriVault
        </a>
        <nav class="row">
            <?php if ($authed): ?>
                <a class="btn sm" href="<?= e(url('dashboard')) ?>">Go to dashboard</a>
            <?php else: ?>
                <a class="btn ghost sm" href="<?= e(url('login')) ?>">Sign in</a>
                <a class="btn sm" href="<?= e(url('register')) ?>">Get started</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?= $this->yieldContent() ?>

<footer class="pub-footer">
    <div class="container spread">
        <span>&copy; <?= e(date('Y')) ?> AgriVault</span>
        <span class="row gap-16">
            <a href="<?= e(url('privacy')) ?>">Privacy</a>
            <a href="<?= e(url('terms')) ?>">Terms</a>
            <a href="<?= e(url('security')) ?>">Security</a>
        </span>
    </div>
</footer>
<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
