<?php
/** Bare layout: error pages, minimal chrome. @var string $title */
$title = $title ?? 'AgriVault';
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
    <main class="container" style="max-width:560px;padding-top:12vh;text-align:center">
        <?= $this->yieldContent() ?>
    </main>
</body>
</html>
