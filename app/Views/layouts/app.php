<?php
/**
 * Authenticated application shell.
 *
 * @var string $title        page title (browser + topbar)
 * @var string $active       active nav key
 */
use App\Core\Auth;
use App\Core\FarmContext;
use App\Support\Str;

$title  = $title ?? 'Dashboard';
$active = $active ?? '';
$user   = Auth::user() ?? [];
$ctx    = FarmContext::has() ? FarmContext::current() : null;

/** @var list<array{key:string,label:string,href:string,icon:string}> $navCommand */
$navCommand = [
    ['key' => 'dashboard',  'label' => 'Dashboard',  'href' => url('dashboard'),  'icon' => 'gauge'],
    ['key' => 'fields',     'label' => 'Fields',     'href' => url('fields'),     'icon' => 'sprout'],
    ['key' => 'map',        'label' => 'Farm map',   'href' => url('map'),        'icon' => 'map'],
    ['key' => 'crops',      'label' => 'Crops',      'href' => url('crops'),      'icon' => 'sprout'],
    ['key' => 'activities', 'label' => 'Activities', 'href' => url('activities'), 'icon' => 'leaf'],
    ['key' => 'calendar',   'label' => 'Calendar',   'href' => url('calendar'),   'icon' => 'calendar'],
    ['key' => 'incidents',  'label' => 'Pest & Disease','href' => url('incidents'), 'icon' => 'alert'],
    ['key' => 'templates',  'label' => 'Seasonal templates', 'href' => url('templates'), 'icon' => 'wheat'],
    ['key' => 'yields',     'label' => 'Yields',     'href' => url('yields'),     'icon' => 'sprout'],
    ['key' => 'finance',    'label' => 'Finance',    'href' => url('finance'),    'icon' => 'wallet'],
    ['key' => 'inventory',  'label' => 'Inventory',  'href' => url('inventory'),  'icon' => 'boxes'],
    ['key' => 'equipment',  'label' => 'Equipment',  'href' => url('equipment'),  'icon' => 'settings'],
    ['key' => 'livestock',  'label' => 'Livestock',  'href' => url('livestock'),  'icon' => 'cow'],
    ['key' => 'reports',    'label' => 'Reports',    'href' => url('reports'),    'icon' => 'bar-chart'],
    ['key' => 'cooperative','label' => 'Cooperatives','href' => url('cooperatives'), 'icon' => 'users'],
];
$navAccount = [
    ['key' => 'weather',    'label' => 'Weather',       'href' => url('weather'),    'icon' => 'sun'],
    ['key' => 'market',     'label' => 'Market prices',  'href' => url('market'),    'icon' => 'bar-chart'],
    ['key' => 'documents',  'label' => 'Documents',      'href' => url('documents'), 'icon' => 'file-text'],
    ['key' => 'climate-events', 'label' => 'Climate Events', 'href' => url('climate-events'), 'icon' => 'alert'],
    ['key' => 'employees',  'label' => 'Employees',      'href' => url('employees'), 'icon' => 'users'],
    ['key' => 'team',       'label' => 'Team',           'href' => url('team'),      'icon' => 'users'],
    ['key' => 'settings',   'label' => 'Farm settings',  'href' => url('settings'),  'icon' => 'settings'],
    ['key' => 'account',    'label' => 'Account',        'href' => url('account'),   'icon' => 'settings'],
];
$navGroups = [
    ['label' => 'Command center', 'items' => $navCommand],
    ['label' => 'Account',        'items' => $navAccount],
];

$initials = Str::initials((string) ($user['name'] ?? $user['email'] ?? '?'));
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — AgriVault</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
    <?= $this->section('head') ?>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app" data-shell>
    <aside class="sidebar" aria-label="Primary">
        <div class="brand">
            <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield-check', 'class' => 'ico']) ?></span>
            <span class="text">
                <span><?= e('AgriVault') ?></span>
                <span class="tag">Farm Records</span>
            </span>
        </div>

        <?php if ($ctx !== null): ?>
            <div class="switcher-slot">
                <?= $this->partial('partials/farm-switcher') ?>
            </div>
        <?php endif; ?>

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
            <div class="userchip">
                <span class="avatar"><?= e($initials) ?></span>
                <div class="who">
                    <div class="name"><?= e((string) ($user['name'] ?? 'Account')) ?></div>
                    <div class="email"><?= e((string) ($user['email'] ?? '')) ?></div>
                </div>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="signout" title="Sign out" aria-label="Sign out">
                        <?= $this->partial('partials/icon', ['name' => 'log-out', 'class' => 'ico']) ?>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="row">
                <button type="button" class="shell-toggle" data-shell-toggle aria-label="Toggle navigation">
                    <?= $this->partial('partials/icon', ['name' => 'menu', 'class' => 'ico']) ?>
                </button>
                <div class="crumbs">
                    <span class="app-name">AgriVault</span>
                    <?= $this->partial('partials/icon', ['name' => 'chevron-right', 'class' => 'ico sep']) ?>
                    <span class="page"><?= e($title) ?></span>
                </div>
            </div>

            <div class="row">
                <?php if ($ctx !== null): ?>
                    <a href="<?= e(url('weather')) ?>" class="icon-btn" aria-label="Weather">
                        <?= $this->partial('partials/icon', ['name' => 'sun', 'class' => 'ico']) ?>
                    </a>
                    <?= $this->partial('partials/notification-bell') ?>
                <?php endif; ?>
            </div>
        </header>

        <main class="content" id="main">
            <?= $this->yieldContent() ?>
        </main>
    </div>
</div>

<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
<?= $this->section('scripts') ?>
</body>
</html>
