<?php
/**
 * @var array<int,array<string,mixed>> $crops
 * @var list<string> $seasons
 * @var string $season
 * @var bool $archived
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Crops</h1>
        <p class="lede"><?= count($crops) ?> <?= $archived ? 'archived' : 'active' ?> planting<?= count($crops) === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($canManage && !$archived): ?>
        <a class="btn" href="<?= e(url('crops/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add planting
        </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('crops')) ?>" class="row wrap mb-16" style="gap:10px">
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:240px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($archived): ?>
        <input type="hidden" name="view" value="archived">
        <a class="btn secondary sm" href="<?= e(url('crops')) ?>">Show active</a>
    <?php else: ?>
        <a class="btn secondary sm" href="<?= e(url('crops?view=archived')) ?>">Show archived</a>
    <?php endif; ?>
    <noscript><button class="btn sm" type="submit">Filter</button></noscript>
</form>

<?php if ($crops === []): ?>
    <div class="empty">
        <div class="h3">Nothing here</div>
        <p><?= $archived ? 'No archived plantings.' : 'Record what’s planted where — crop, variety, season and dates.' ?></p>
        <?php if ($canManage && !$archived): ?>
            <p class="mt-16"><a class="btn" href="<?= e(url('crops/create')) ?>">Add a planting</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Crop</th><th>Field</th><th>Variety</th><th>Season</th>
                    <th class="num">Area (ha)</th><th>Planted</th><th>Exp. harvest</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($crops as $c): ?>
                <tr>
                    <td><a href="<?= e(url('crops/' . rawurlencode((string) $c['id']))) ?>"><strong><?= e((string) $c['crop_name']) ?></strong></a></td>
                    <td><?= e((string) $c['field_name']) ?></td>
                    <td class="muted"><?= e((string) $c['variety'] ?: '—') ?></td>
                    <td class="small"><?= e((string) $c['season']) ?></td>
                    <td class="num"><?= e(number_format((float) $c['area_planted'], 2)) ?></td>
                    <td class="small"><?= e(Dates::forDisplay((string) $c['planting_date'])) ?></td>
                    <td class="small"><?= e(Dates::forDisplay((string) $c['expected_harvest_date'])) ?></td>
                    <td>
                        <?php $st = (string) $c['status']; ?>
                        <span class="badge <?= $st === 'Active' ? 'green' : ($st === 'Harvested' ? 'blue' : 'red') ?>"><?= e($st) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
