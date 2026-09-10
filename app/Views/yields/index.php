<?php
/**
 * @var array<int,array<string,mixed>> $rows
 * @var float $totalKg
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Yields</h1>
        <p class="lede"><?= count($rows) ?> harvest record<?= count($rows) === 1 ? '' : 's' ?> ·
            <?= e(number_format($totalKg, 0)) ?> kg total</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('yields/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Record yield
        </a>
    <?php endif; ?>
</div>

<?php if ($rows === []): ?>
    <div class="empty">
        <div class="h3">No yields recorded</div>
        <p>Log each harvest against its planting — quantity, unit and (for bags/crates) the weight per unit.</p>
        <?php if ($canManage): ?><p class="mt-16"><a class="btn" href="<?= e(url('yields/create')) ?>">Record a yield</a></p><?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Date</th><th>Crop</th><th>Field</th><th>Season</th><th class="num">Quantity</th><th class="num">kg</th><?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="small nowrap"><?= e(Dates::forDisplay((string) $r['harvest_date'])) ?></td>
                    <td><strong><?= e((string) $r['crop_name']) ?></strong> <span class="muted small"><?= e((string) $r['variety']) ?></span></td>
                    <td><?= e((string) $r['field_name']) ?></td>
                    <td class="small"><?= e((string) $r['season']) ?></td>
                    <td class="num"><?= e(rtrim(rtrim((string) $r['quantity'], '0'), '.')) ?> <?= e((string) $r['unit']) ?></td>
                    <td class="num"><?= e(number_format((float) $r['quantity_kg'], 0)) ?></td>
                    <?php if ($canManage): ?>
                        <td class="num nowrap">
                            <a class="btn sm ghost" href="<?= e(url('yields/' . rawurlencode((string) $r['id']) . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(url('yields/' . rawurlencode((string) $r['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this yield record?')">
                                <?= csrf_field() ?>
                                <button class="btn sm ghost danger" type="submit">Delete</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
