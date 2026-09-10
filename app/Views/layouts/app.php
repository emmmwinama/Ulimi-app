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

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

/** @var list<array{key:string,label:string,href:string,icon:string}> $navMain */
$navMain = [
    ['key' => 'dashboard',  'label' => 'Dashboard',  'href' => url('dashboard'),  'icon' => 'gauge'],
    ['key' => 'fields',     'label' => 'Fields',     'href' => url('fields'),     'icon' => 'map'],
    ['key' => 'crops',      'label' => 'Crops',      'href' => url('crops'),      'icon' => 'sprout'],
    ['key' => 'activities', 'label' => 'Activities', 'href' => url('activities'), 'icon' => 'leaf'],
    ['key' => 'yields',     'label' => 'Yields',     'href' => url('yields'),     'icon' => 'sprout'],
];
$navBusiness = [
    ['key' => 'finance',   'label' => 'Finance',   'href' => url('finance'),    'icon' => 'wallet'],
    ['key' => 'inventory', 'label' => 'Inventory', 'href' => url('inventory'),  'icon' => 'boxes'],
];
$navPeople = [
    ['key' => 'employees', 'label' => 'Employees', 'href' => url('employees'), 'icon' => 'users'],
    ['key' => 'team',      'label' => 'Team',      'href' => url('team'),      'icon' => 'users'],
];
$navUpcoming = [
    ['label' => 'Livestock', 'icon' => 'cow'],
    ['label' => 'Reports',   'icon' => 'bar-chart'],
    ['label' => 'Documents', 'icon' => 'file-text'],
];
$navManage = [
    ['key' => 'account', 'label' => 'Account', 'href' => url('account'), 'icon' => 'settings'],
];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — AgriVault</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app" data-shell>
    <aside class="sidebar" aria-label="Primary">
        <div class="brand">
            <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield', 'class' => 'ico']) ?></span>
            AgriVault
        </div>

        <nav class="nav-group" aria-label="Main">
            <?php foreach ($navMain as $item): ?>
                <a class="nav-item <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>">
                    <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-group">
            <div class="eyebrow">Business</div>
            <?php foreach ($navBusiness as $item): ?>
                <a class="nav-item <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>">
                    <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="nav-group">
            <div class="eyebrow">People</div>
            <?php foreach ($navPeople as $item): ?>
                <a class="nav-item <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>">
                    <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="nav-group">
            <div class="eyebrow">Arriving soon</div>
            <?php foreach ($navUpcoming as $item): ?>
                <span class="nav-item" aria-disabled="true" style="opacity:.45;cursor:default">
                    <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                    <span><?= e($item['label']) ?></span>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="nav-group">
            <div class="eyebrow">Manage</div>
            <?php foreach ($navManage as $item): ?>
                <a class="nav-item <?= $active === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>">
                    <?= $this->partial('partials/icon', ['name' => $item['icon'], 'class' => 'ico']) ?>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:auto">
            <form method="post" action="<?= e(url('logout')) ?>">
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
                <span class="page-title"><?= e($title) ?></span>
            </div>

            <div class="row">
                <?php if ($ctx !== null): ?>
                    <?= $this->partial('partials/farm-switcher') ?>
                <?php endif; ?>

                <details class="chip-select">
                    <summary>
                        <?= e(Str::initials((string) ($user['name'] ?? $user['email'] ?? '?'))) ?>
                        <?= $this->partial('partials/icon', ['name' => 'chevron-down', 'class' => 'ico']) ?>
                    </summary>
                    <div class="menu">
                        <div style="padding:8px 10px">
                            <div style="font-weight:700"><?= e((string) ($user['name'] ?? 'Account')) ?></div>
                            <div class="small muted"><?= e((string) ($user['email'] ?? '')) ?></div>
                        </div>
                        <hr>
                        <a href="<?= e(url('account')) ?>">Account settings</a>
                        <form method="post" action="<?= e(url('logout')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit">Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main class="content" id="main">
            <?= $this->yieldContent() ?>
        </main>
    </div>
</div>

<?= $this->partial('partials/flash') ?>
<script src="<?= e(asset('app.js')) ?>" defer></script>
</body>
</html>
