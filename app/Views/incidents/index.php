<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var list<string> $types @var list<string> $severities @var list<string> $statuses
 * @var array{type:string,status:string} $filters
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$sevBadge = ['mild' => '', 'moderate' => 'amber', 'severe' => 'red'];
$statusBadge = ['open' => 'red', 'treated' => 'amber', 'resolved' => 'green'];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Pest &amp; Disease Log</h1>
        <p class="lede"><?= count($rows) ?> incident<?= count($rows) === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('incidents/create')) ?>">Report incident</a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('incidents')) ?>" class="row mb-16px" style="gap:8px;flex-wrap:wrap">
    <select class="select" name="type" onchange="this.form.submit()">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e($label($t)) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="select" name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($label($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($filters['type'] !== '' || $filters['status'] !== ''): ?>
        <a class="btn ghost sm" href="<?= e(url('incidents')) ?>">Clear</a>
    <?php endif; ?>
</form>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No incidents logged</div><p>Report a pest, disease or other crop issue as soon as you spot it.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Crop / field</th><th>Type</th><th>Severity</th><th>Status</th><th>Reported</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a href="<?= e(url('incidents/' . rawurlencode((string) $r['id']))) ?>"><strong><?= e((string) $r['crop_name']) ?></strong></a><div class="small muted"><?= e((string) $r['field_name']) ?></div></td>
                <td><?= e($label((string) $r['type'])) ?></td>
                <td><span class="badge <?= $sevBadge[$r['severity']] ?? '' ?>"><?= e($label((string) $r['severity'])) ?></span></td>
                <td><span class="badge <?= $statusBadge[$r['status']] ?? '' ?>"><?= e($label((string) $r['status'])) ?></span></td>
                <td class="small"><?= e(Dates::forDisplay((string) $r['reported_date'])) ?></td>
                <td class="num nowrap">
                    <a class="btn sm ghost" href="<?= e(url('incidents/' . rawurlencode((string) $r['id']))) ?>">View</a>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= e(url('incidents/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                              style="display:inline" onsubmit="return confirm('Delete this incident?')">
                            <?= csrf_field() ?>
                            <button class="btn sm ghost danger" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php $this->stop(); ?>
