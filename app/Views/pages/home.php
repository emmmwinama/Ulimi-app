<?php $this->layout('layouts/public'); ?>
<?php $this->start('content'); ?>
<section class="hero">
    <div class="container">
        <p class="eyebrow">Farm records &amp; evidence</p>
        <h1 class="h1">The record vault your farm can prove.</h1>
        <p class="lede">
            Capture fields, crops, activities, labour, finance and livestock in one place —
            then hand a buyer, lender, auditor or insurer exactly the evidence they ask for.
        </p>
        <div class="cta">
            <a class="btn" href="<?= e(url('register')) ?>">Start free</a>
            <a class="btn secondary" href="<?= e(url('login')) ?>">Sign in</a>
        </div>
    </div>
</section>

<section class="feature-grid">
    <div class="container grid cols-3">
        <?php
        $features = [
            ['leaf', 'Every record in one vault', 'Fields, crops, activities, inputs, labour, finance, inventory and livestock — structured, searchable, and linked.'],
            ['shield', 'Built to be proven', 'Buyer packs, loan-readiness files, audit and insurance evidence generated from your real records.'],
            ['users', 'Team access, done right', 'Owner, manager, agronomist, accountant and field-worker roles with per-resource permissions.'],
            ['bar-chart', 'Season economics', 'Cashflow, crop and field profitability, cost per hectare and per kilogram.'],
            ['gauge', 'Credit readiness', 'A transparent score from your own data — cashflow, activity cost and revenue factors.'],
            ['map', 'Field mapping', 'Draw field boundaries and management zones; mark boreholes, sheds, gates and roads.'],
        ];
        foreach ($features as [$icon, $t, $d]): ?>
            <div class="feature">
                <div class="ico"><?= $this->partial('partials/icon', ['name' => $icon, 'class' => 'ico']) ?></div>
                <h3 class="h3"><?= e($t) ?></h3>
                <p class="muted small mt-8"><?= e($d) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php $this->stop(); ?>
