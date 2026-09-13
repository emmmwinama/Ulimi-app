<?php
/**
 * Admin back-office shell. Separate from the customer app shell — different
 * nav, different session domain (App\Core\AdminAuth). Visually distinguished
 * from the customer app only by a solid slate sidebar (vs. the gradient one)
 * and the "Admin" breadcrumb, matching the reference build.
 *
 * @var string $title
 * @var string $active
 */
use App\Core\AdminAuth;

$title = $title ?? 'Admin';
$active = $active ?? '';
$admin = AdminAuth::admin() ?? [];

$navGroups = [
    [
        'label' => 'Management',
        'items' => [
            ['key' => 'dashboard',     'label' => 'Overview',      'href' => url('admin'),               'icon' => 'gauge'],
            ['key' => 'users',         'label' => 'Users',         'href' => url('admin/users'),         'icon' => 'users'],
            ['key' => 'subscriptions', 'label' => 'Subscriptions', 'href' => url('admin/subscriptions'), 'icon' => 'wallet'],
            ['key' => 'payments',      'label' => 'Payments',      'href' => url('admin/payments'),      'icon' => 'receipt'],
        ],
    ],
    [
        'label' => 'Configuration',
        'items' => [
            ['key' => 'tiers',  'label' => 'Tiers',         'href' => url('admin/tiers'),  'icon' => 'boxes'],
            ['key' => 'market', 'label' => 'Market data',   'href' => url('admin/market'), 'icon' => 'bar-chart'],
        ],
    ],
    [
        'label' => 'Content',
        'items' => [
            ['key' => 'cms',       'label' => 'Site content', 'href' => url('admin/cms'),       'icon' => 'file-text'],
            ['key' => 'inquiries', 'label' => 'Inquiries',    'href' => url('admin/inquiries'), 'icon' => 'mail'],
        ],
    ],
];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — AgriVault Admin</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app admin-shell" data-shell>
    <aside class="sidebar" aria-label="Admin">
        <div class="brand">
            <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield-check', 'class' => 'ico']) ?></span>
            <span class="text">
                <span>AgriVault</span>
                <span class="tag">Farm Records</span>
            </span>
        </div>

        <button type="button" class="collapse-toggle" data-collapse-toggle aria-label="Collapse sidebar">
            <?= $this->partial('partials/icon', ['name' => 'chevron-left', 'class' => 'ico']) ?>
        </button>

        <nav class="nav-scroll" aria-label="Main">
            <?php foreach ($navGroups as $group): ?>
                <div class="nav-group">
                    <div class="eyebrow"><?= e($group['label']) ?></div>
                    <div class="items">
                        <?php foreach ($group['items'] as $item): ?>
                            <a class="nav-item <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>">
                                <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="foot">
            <form method="post" action="<?= e(url('admin/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="nav-item">
                    <?= $this->partial('partials/icon', ['name' => 'log-out', 'class' => 'ico']) ?>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="row">
                <button type="button" class="shell-toggle" data-shell-toggle aria-label="Toggle navigation">
                    <?= $this->partial('partials/icon', ['name' => 'menu', 'class' => 'ico']) ?>
                </button>
                <div class="crumbs">
                    <span class="app-name">Admin</span>
                    <?= $this->partial('partials/icon', ['name' => 'chevron-right', 'class' => 'ico sep']) ?>
                    <span class="page"><?= e($title) ?></span>
                </div>
            </div>
            <div class="row">
                <span class="small muted"><?= e((string) ($admin['name'] ?? '')) ?></span>
                <span class="avatar"><?= e(strtoupper(substr((string) ($admin['name'] ?? 'A'), 0, 1))) ?></span>
            </div>
        </header>
        <main class="content" id="main"><?= $this->yieldContent() ?></main>
    </div>
</div>
<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
