<?php
/**
 * Print-optimised report layout. Screen shows chrome (nav-back, print button);
 * @media print strips it to a clean, brand-compliant document the browser's
 * own "Print > Save as PDF" turns into a PDF with no server-side PDF library.
 *
 * @var string $title
 * @var string $farmName
 * @var string $purpose      one-line description of what this document is for
 * @var string $dateRange    e.g. "All seasons" or "2025/26 Rain Season"
 * @var string $generatedAt
 * @var ?string $backUrl     route path for the "back" link (default: reports)
 * @var ?string $backLabel   label for the "back" link (default: Reports)
 * @var bool $public         true for the token-gated public share view — hides the back link (there is nothing authenticated to go back to) and shows a consent note instead
 */
$title = $title ?? 'Report';
$public = $public ?? false;
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> — AgriVault</title>
    <link rel="stylesheet" href="<?= e(asset('app.css')) ?>">
    <style>
        .print-toolbar { position:sticky; top:0; z-index:30; background:var(--surface); border-bottom:1px solid var(--line); padding:12px 20px; display:flex; justify-content:space-between; align-items:center; }
        .doc { max-width:900px; margin:0 auto; padding:32px 24px 64px; }
        .doc-cover { background:var(--navy); color:#fff; border-radius:14px; padding:28px 32px; margin-bottom:28px; }
        .doc-cover .brand { display:flex; align-items:center; gap:10px; font-weight:800; margin-bottom:18px; }
        .doc-cover .brand .mark { width:28px; height:28px; border-radius:8px; background:linear-gradient(150deg,var(--blue),var(--cyan)); display:grid; place-items:center; }
        .doc-cover h1 { font-size:1.5rem; font-weight:800; margin:0 0 6px; }
        .doc-cover .meta { color:#CBD5E1; font-size:.86rem; display:grid; gap:3px; margin-top:14px }
        .doc-section { margin-bottom:28px; page-break-inside:avoid; }
        .doc-section h2 { font-size:1rem; font-weight:800; color:var(--navy); border-bottom:2px solid var(--blue-050); padding-bottom:8px; margin-bottom:12px; }
        .doc-footer { margin-top:40px; padding-top:14px; border-top:1px solid var(--line); color:var(--text-faint); font-size:.78rem; display:flex; justify-content:space-between; }
        @media print {
            .print-toolbar, .no-print { display:none !important; }
            body { background:#fff; }
            .doc { max-width:none; padding:0; }
            .doc-cover { background:var(--navy) !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            table.data thead th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            a { color:inherit; text-decoration:none; }
            .doc-section { break-inside:avoid; }
        }
    </style>
</head>
<body>
<div class="print-toolbar no-print">
    <?php if ($public): ?>
        <span class="small muted">Shared by consent of the farm owner</span>
    <?php else: ?>
        <a class="btn ghost sm" href="<?= e(url($backUrl ?? 'reports')) ?>">← <?= e($backLabel ?? 'Reports') ?></a>
    <?php endif; ?>
    <button type="button" class="btn sm" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="doc">
    <div class="doc-cover">
        <div class="brand">
            <span class="mark"><?= $this->partial('partials/icon', ['name' => 'shield', 'class' => 'ico']) ?></span>
            AgriVault
        </div>
        <h1><?= e($title) ?></h1>
        <div class="meta">
            <span>Farm: <?= e($farmName ?? '') ?></span>
            <span>Period: <?= e($dateRange ?? 'All seasons') ?></span>
            <?php if (!empty($purpose)): ?><span>Purpose: <?= e($purpose) ?></span><?php endif; ?>
            <?php if ($public): ?><span>Shared on: <?= e($generatedAt ?? '') ?> UTC</span><?php endif; ?>
        </div>
    </div>

    <?= $this->yieldContent() ?>

    <div class="doc-footer">
        <span>AgriVault — farm record vault</span>
        <span>Generated <?= e($generatedAt ?? gmdate('Y-m-d H:i')) ?> UTC</span>
    </div>
</div>
</body>
</html>
