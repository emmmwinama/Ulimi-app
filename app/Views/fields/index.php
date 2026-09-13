<?php
/**
 * @var array<int,array<string,mixed>> $fields
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;

$totalArea = array_sum(array_map(static fn ($f) => (float) $f['total_area'], $fields));
$cultArea  = array_sum(array_map(static fn ($f) => (float) $f['cultivatable_area'], $fields));
$mappedCount = count(array_filter($fields, static fn ($f) => !empty($f['boundary_points'])));
?>
<?php $this->start('content'); ?>

<div class="page-head">
    <div>
        <h1 class="h1">Fields</h1>
        <p class="lede"><?= count($fields) ?> field<?= count($fields) === 1 ? '' : 's' ?> ·
            <?= e(number_format($totalArea, 1)) ?> ha total ·
            <?= $mappedCount ?> mapped</p>
    </div>
    <div class="row">
        <a class="btn secondary" href="<?= e(url('map')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'map', 'class' => 'ico']) ?> Farm map
        </a>
        <?php if ($canManage): ?>
            <a class="btn" href="#add-field">
                <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add field
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($fields === []): ?>
    <div class="empty">
        <span class="icon-box lg teal" style="margin:0 auto 16px">
            <?= $this->partial('partials/icon', ['name' => 'map', 'class' => 'ico']) ?>
        </span>
        <div class="h3">No fields yet</div>
        <p>Add your land parcels — area, soil type and location. Crops, activities and maps all hang off fields.</p>
        <?php if ($canManage): ?>
            <p class="mt-16px"><a class="btn" href="#add-field">Add your first field</a></p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid cols-3 mb-16px">
        <div class="stat">
            <div class="label">Total fields</div>
            <div class="value"><?= count($fields) ?></div>
        </div>
        <div class="stat">
            <div class="label">Total area</div>
            <div class="value" style="color:var(--blue)"><?= e(number_format($totalArea, 1)) ?> ha</div>
        </div>
        <div class="stat">
            <div class="label">GPS mapped</div>
            <div class="value" style="color:var(--sky-600)"><?= $mappedCount ?> / <?= count($fields) ?></div>
        </div>
    </div>

    <div class="grid cols-3">
        <?php foreach ($fields as $f):
            $mapped = !empty($f['boundary_points']);
            $cropNames = array_filter(array_map('trim', explode(',', (string) ($f['crop_names'] ?? ''))));
        ?>
            <div class="card" style="display:flex;flex-direction:column">
                <div class="card-body" style="flex:1">
                    <div class="spread" style="align-items:flex-start;margin-bottom:12px">
                        <div class="row" style="gap:10px">
                            <span class="icon-box teal">
                                <?= $this->partial('partials/icon', ['name' => 'leaf', 'class' => 'ico']) ?>
                            </span>
                            <div>
                                <h3 class="h3"><?= e((string) $f['name']) ?></h3>
                                <p style="font-size:.75rem;color:var(--text-faint)"><?= e((string) $f['soil_type']) ?> soil</p>
                            </div>
                        </div>
                        <span class="badge <?= $mapped ? 'green' : '' ?>"><?= $mapped ? 'Mapped' : 'No boundary' ?></span>
                    </div>

                    <div class="grid cols-2" style="gap:8px;margin-bottom:12px">
                        <div class="mini-stat">
                            <p class="label">Total area</p>
                            <p class="value" style="color:var(--teal)"><?= e(number_format((float) $f['total_area'], 2)) ?> ha</p>
                        </div>
                        <div class="mini-stat">
                            <p class="label">Cultivatable</p>
                            <p class="value"><?= e(number_format((float) $f['cultivatable_area'], 2)) ?> ha</p>
                        </div>
                    </div>

                    <?php if ($cropNames !== []): ?>
                        <div class="row wrap" style="gap:4px;margin-bottom:8px">
                            <?php foreach (array_slice($cropNames, 0, 3) as $name): ?>
                                <span class="badge green" style="font-size:.625rem"><?= e($name) ?></span>
                            <?php endforeach; ?>
                            <?php if (count($cropNames) > 3): ?>
                                <span class="badge" style="font-size:.625rem">+<?= count($cropNames) - 3 ?> more</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($f['notes'])): ?>
                        <p style="font-size:.75rem;font-style:italic;color:var(--text-faint)"><?= e((string) $f['notes']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="spread card-foot">
                    <p style="font-size:.75rem;color:var(--text-faint)">Added <?= e(Dates::forDisplay((string) $f['created_at'])) ?></p>
                    <div class="row" style="gap:4px">
                        <a href="<?= e(url('fields/' . rawurlencode((string) $f['id']) . '/map')) ?>" title="<?= $mapped ? 'Map' : 'Draw' ?>"
                           class="icon-box sm teal">
                            <?= $this->partial('partials/icon', ['name' => 'map', 'class' => 'ico ico-sm']) ?>
                        </a>
                        <?php if ($canManage): ?>
                            <a href="#edit-field-<?= e((string) $f['id']) ?>" title="Edit"
                               class="icon-box sm muted">
                                <?= $this->partial('partials/icon', ['name' => 'pencil', 'class' => 'ico ico-sm']) ?>
                            </a>
                            <form method="post" action="<?= e(url('fields/' . rawurlencode((string) $f['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this field?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Delete"
                                        class="icon-box sm red" style="border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage): ?>
    <div class="slide-over" id="add-field">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Add new field</h2>
                    <p class="small muted mt-8px">Fill in the field details below</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('fields')) ?>" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/fields/fields', ['field' => null]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Add field</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($fields as $f): ?>
        <div class="slide-over" id="edit-field-<?= e((string) $f['id']) ?>">
            <a href="#" class="scrim" aria-label="Close"></a>
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="h3">Edit field</h2>
                        <p class="small muted mt-8px">Update <?= e((string) $f['name']) ?>’s details</p>
                    </div>
                    <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
                </div>
                <form method="post" action="<?= e(url('fields/' . rawurlencode((string) $f['id']))) ?>" style="display:contents">
                    <?= csrf_field() ?>
                    <?= method_field('PUT') ?>
                    <div class="panel-body stack">
                        <?= $this->partial('partials/fields/fields', ['field' => $f]) ?>
                    </div>
                    <div class="panel-foot">
                        <a href="#" class="btn ghost block">Cancel</a>
                        <button type="submit" class="btn block">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php $this->stop(); ?>
