<?php
/**
 * @var array<int,array<string,mixed>> $fields
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
$totalArea = array_sum(array_map(static fn ($f) => (float) $f['total_area'], $fields));
$cultArea  = array_sum(array_map(static fn ($f) => (float) $f['cultivatable_area'], $fields));
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Fields</h1>
        <p class="lede"><?= count($fields) ?> field<?= count($fields) === 1 ? '' : 's' ?> ·
            <?= e(number_format($totalArea, 1)) ?> ha total ·
            <?= e(number_format($cultArea, 1)) ?> ha cultivatable</p>
    </div>
    <?php if ($canManage): ?>
        <a class="btn" href="<?= e(url('fields/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add field
        </a>
    <?php endif; ?>
</div>

<?php if ($fields === []): ?>
    <div class="empty">
        <div class="h3">No fields yet</div>
        <p>Add your land parcels — area, soil type and location. Crops, activities and maps all hang off fields.</p>
        <?php if ($canManage): ?>
            <p class="mt-16"><a class="btn" href="<?= e(url('fields/create')) ?>">Add your first field</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Soil</th>
                    <th class="num">Total (ha)</th>
                    <th class="num">Cultivatable (ha)</th>
                    <th class="num">Active crops</th>
                    <th>Added</th>
                    <?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fields as $f): ?>
                <tr>
                    <td>
                        <strong><?= e((string) $f['name']) ?></strong>
                        <?php if ($f['location_lat'] !== null): ?>
                            <div class="small muted mono"><?= e(number_format((float) $f['location_lat'], 4)) ?>, <?= e(number_format((float) $f['location_lng'], 4)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e((string) $f['soil_type']) ?></td>
                    <td class="num"><?= e(number_format((float) $f['total_area'], 2)) ?></td>
                    <td class="num"><?= e(number_format((float) $f['cultivatable_area'], 2)) ?></td>
                    <td class="num"><?= e((string) $f['active_crops']) ?></td>
                    <td class="small muted"><?= e(Dates::forDisplay((string) $f['created_at'])) ?></td>
                    <?php if ($canManage): ?>
                        <td class="num nowrap">
                            <a class="btn sm ghost" href="<?= e(url('fields/' . rawurlencode((string) $f['id']) . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(url('fields/' . rawurlencode((string) $f['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this field?')">
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
