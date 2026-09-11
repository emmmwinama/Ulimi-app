<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var list<string> $categories @var list<string> $statuses
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Money;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$statusBadge = ['active' => 'green', 'under_repair' => 'amber', 'retired' => ''];
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Equipment</h1>
        <p class="lede"><?= count($rows) ?> item<?= count($rows) === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('equipment/create')) ?>">Add equipment</a>
    <?php endif; ?>
</div>

<?php if ($rows === []): ?>
    <div class="empty"><div class="h3">No equipment recorded</div><p>Track tractors, irrigation gear, tools and vehicles — with a maintenance history for each.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Name</th><th>Category</th><th>Status</th><th class="num">Maintenance logs</th><th class="num">Maintenance cost</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><a href="<?= e(url('equipment/' . rawurlencode((string) $r['id']))) ?>"><strong><?= e((string) $r['name']) ?></strong></a></td>
                <td><?= e($label((string) $r['category'])) ?></td>
                <td><span class="badge <?= $statusBadge[$r['status']] ?? '' ?>"><?= e($label((string) $r['status'])) ?></span></td>
                <td class="num"><?= (int) $r['log_count'] ?></td>
                <td class="num"><?= e(Money::format((float) $r['maintenance_cost'])) ?></td>
                <td class="num nowrap">
                    <a class="btn sm ghost" href="<?= e(url('equipment/' . rawurlencode((string) $r['id']))) ?>">View</a>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                              style="display:inline" onsubmit="return confirm('Delete this equipment record?')">
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
