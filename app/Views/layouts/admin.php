<?php
/**
 * Admin back-office shell. Separate from the customer app shell — different
 * nav, different session domain (App\Core\AdminAuth), visually distinguished
 * with an amber "ADMIN" mark so nobody confuses the two areas.
 *
 * @var string $title
 * @var string $active
 */
use App\Core\AdminAuth;

$title = $title ?? 'Admin';
$active = $active ?? '';
$admin = AdminAuth::admin() ?? [];

$nav = [
    ['key' => 'dashboard',     'label' => 'Overview',      'href' => url('admin'),                  'icon' => 'gauge'],
    ['key' => 'users',         'label' => 'Users',         'href' => url('admin/users'),             'icon' => 'users'],
    ['key' => 'subscriptions', 'label' => 'Subscriptions', 'href' => url('admin/subscriptions'),     'icon' => 'wallet'],
    ['key' => 'tiers',         'label' => 'Tiers',         'href' => url('admin/tiers'),             'icon' => 'boxes'],
    ['key' => 'market',        'label' => 'Market data',   'href' => url('admin/market'),            'icon' => 'bar-chart'],
    ['key' => 'cms',           'label' => 'Site content',  'href' => url('admin/cms'),               'icon' => 'file-text'],
    ['key' => 'inquiries',     'label' => 'Inquiries',     'href' => url('admin/inquiries'),         'icon' => 'mail'],
];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — AgriVault Admin</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
    <style>.sidebar{background:#1C1206}.sidebar a.nav-item.is-active{background:#B45309}</style>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app" data-shell>
    <aside class="sidebar" aria-label="Admin">
        <div class="brand">
            <span class="mark" style="background:linear-gradient(150deg,#B45309,#F59E0B)"><?= $this->partial('partials/icon', ['name' => 'shield', 'class' => 'ico']) ?></span>
            AgriVault <span class="badge amber" style="margin-left:4px">ADMIN</span>
        </div>
        <nav class="nav-group" aria-label="Main">
            <?php foreach ($nav as $item): ?>
                <a class="nav-item <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>">
                    <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div style="margin-top:auto">
            <form method="post" action="<?= e(url('admin/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="nav-item"><?= $this->partial('partials/icon', ['name' => 'log-out', 'class' => 'ico']) ?><span>Sign out</span></button>
            </form>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <div class="row">
                <button type="button" class="shell-toggle" data-shell-toggle aria-label="Toggle navigation"><?= $this->partial('partials/icon', ['name' => 'menu', 'class' => 'ico']) ?></button>
                <span class="page-title"><?= e($title) ?></span>
            </div>
            <div class="row"><span class="small muted"><?= e((string) ($admin['name'] ?? '')) ?></span></div>
        </header>
        <main class="content" id="main"><?= $this->yieldContent() ?></main>
    </div>
</div>
<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
