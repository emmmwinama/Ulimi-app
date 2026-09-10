<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var array<int,array<string,mixed>> $fields
 * @var list<string> $types
 * @var array<string,string> $filters
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$total = array_sum(array_map(static fn ($r) => (float) $r['total_cost'], $rows));
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Activities</h1>
        <p class="lede"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?> ·
            <?= e(Money::format($total)) ?> total cost</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('activities/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Log activity
        </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('activities')) ?>" class="row wrap mb-16" style="gap:10px">
    <select class="select" name="field_id" onchange="this.form.submit()" style="max-width:200px">
        <option value="">All fields</option>
        <?php foreach ($fields as $f): ?>
            <option value="<?= e((string) $f['id']) ?>" <?= $filters['field_id'] === (string) $f['id'] ? 'selected' : '' ?>><?= e((string) $f['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="type" onchange="this.form.submit()" style="max-width:200px">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
    </select>
    <input class="input" type="date" name="from" value="<?= e($filters['from']) ?>" style="max-width:160px" aria-label="From date">
    <input class="input" type="date" name="to" value="<?= e($filters['to']) ?>" style="max-width:160px" aria-label="To date">
    <button class="btn secondary sm" type="submit">Filter</button>
    <?php if (array_filter($filters)): ?><a class="btn ghost sm" href="<?= e(url('activities')) ?>">Clear</a><?php endif; ?>
</form>

<?php if ($rows === []): ?>
    <div class="empty">
        <div class="h3">No activities</div>
        <p>Log land prep, planting, spraying, weeding and harvest — with the labour, inputs and other costs behind each.</p>
        <?php if ($canManage): ?><p class="mt-16"><a class="btn" href="<?= e(url('activities/create')) ?>">Log your first activity</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr><th>Date</th><th>Type</th><th>Field</th><th>Crop</th><th>Responsible</th><th class="num">Cost</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="small nowrap"><?= e(Dates::forDisplay((string) $r['date'])) ?></td>
                    <td><a href="<?= e(url('activities/' . rawurlencode((string) $r['id']))) ?>"><strong><?= e((string) $r['activity_type']) ?></strong></a></td>
                    <td><?= e((string) $r['field_name']) ?></td>
                    <td class="muted"><?= e((string) ($r['crop_name'] ?? '—')) ?></td>
                    <td class="muted small"><?= e((string) ($r['responsible_person_name'] ?: '—')) ?></td>
                    <td class="num"><?= e(Money::format((float) $r['total_cost'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
