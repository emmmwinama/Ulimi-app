<?php
/**
 * @var array{total_head:int,by_type:list<array{name:string,icon:string,head:int}>,production_value:float,expenses:float,sales:float} $stats
 * @var array<int,array<string,mixed>> $types
 * @var array<int,array<string,mixed>> $animals
 * @var string $statusF
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Livestock</h1>
        <p class="lede"><?= e((string) $stats['total_head']) ?> active head across <?= count($types) ?> type<?= count($types) === 1 ? '' : 's' ?></p>
    </div>
    <?php if ($canManage && $types !== []): ?>
        <a class="btn" href="<?= e(url('livestock/animals/create')) ?>">
            <?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico']) ?> Add animal
        </a>
    <?php endif; ?>
</div>

<div class="grid cols-4 mb-16">
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--teal-pale)"><?= $this->partial('partials/icon', ['name' => 'cow', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem"><?= e((string) $stats['total_head']) ?></div>
        <div class="label">Active head</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--blue-050);color:var(--blue)"><?= $this->partial('partials/icon', ['name' => 'trend-up', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem"><?= e(Money::compact($stats['production_value'])) ?></div>
        <div class="label">Production value</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--red-050);color:var(--red-text)"><?= $this->partial('partials/icon', ['name' => 'trend-down', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem"><?= e(Money::compact($stats['expenses'])) ?></div>
        <div class="label">Expenses</div>
    </div>
    <div class="stat">
        <div class="icon-row"><span class="icon-box" style="background:var(--teal-pale)"><?= $this->partial('partials/icon', ['name' => 'wallet', 'class' => 'ico']) ?></span></div>
        <div class="value" style="font-size:1.4rem"><?= e(Money::compact($stats['sales'])) ?></div>
        <div class="label">Sales</div>
    </div>
</div>

<div class="grid cols-2 mb-24">
    <div class="card">
        <div class="card-head"><h2 class="h2">Types</h2></div>
        <div class="card-body">
            <?php if ($types === []): ?>
                <p class="muted">No types yet. Add one to start recording animals.</p>
            <?php else: ?>
                <ul style="list-style:none;padding:0;margin:0 0 12px">
                    <?php foreach ($types as $t): ?>
                        <li class="spread" style="padding:8px 0;border-bottom:1px solid var(--line)">
                            <span><strong><?= e((string) $t['name']) ?></strong> <span class="muted small"><?= e((string) $t['category']) ?></span></span>
                            <span class="row">
                                <span class="badge"><?= e((string) $t['head']) ?> head</span>
                                <?php if ($canManage): ?>
                                    <form method="post" action="<?= e(url('livestock/types/' . rawurlencode((string) $t['id']) . '/delete')) ?>" onsubmit="return confirm('Remove this type?')">
                                        <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">✕</button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('livestock/types')) ?>" class="row wrap" style="gap:8px;align-items:flex-end">
                    <?= csrf_field() ?>
                    <div class="field flex-1" style="min-width:150px">
                        <label for="f_name" class="small">Type name</label>
                        <input class="input" id="f_name" name="name" required placeholder="Cattle, Goats, Chickens">
                    </div>
                    <div class="field" style="min-width:120px">
                        <label for="f_category" class="small">Category</label>
                        <input class="input" id="f_category" name="category" placeholder="Ruminant, Poultry">
                    </div>
                    <button class="btn" type="submit">Add type</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Herd composition</h2></div>
        <div class="card-body">
            <?php if ($stats['by_type'] === []): ?>
                <p class="muted">No animals recorded.</p>
            <?php else: ?>
                <?php $maxHead = max(1, ...array_map(static fn ($r) => $r['head'], $stats['by_type'])); ?>
                <?php foreach ($stats['by_type'] as $bt): ?>
                    <div style="margin-bottom:10px">
                        <div class="spread small"><span><?= e($bt['name']) ?></span><span class="muted"><?= e((string) $bt['head']) ?></span></div>
                        <div style="height:8px;background:var(--surface-2);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:<?= (int) round($bt['head'] / $maxHead * 100) ?>%;background:var(--blue)"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-head">
    <h2 class="h2">Animals</h2>
    <form method="get" action="<?= e(url('livestock')) ?>">
        <select class="select" name="status" onchange="this.form.submit()" style="max-width:160px">
            <option value="">All statuses</option>
            <?php foreach (['Active', 'Sold', 'Dead', 'Culled', 'Lost'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $statusF === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($animals === []): ?>
    <div class="empty"><div class="h3">No animals</div><p>Register animals with tags, breed, sex and acquisition details.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Tag / name</th><th>Type</th><th>Sex</th><th>Breed</th><th class="num">Weight</th><th>Status</th><th>Acquired</th></tr></thead>
        <tbody>
        <?php foreach ($animals as $a): ?>
            <tr>
                <td><a href="<?= e(url('livestock/animals/' . rawurlencode((string) $a['id']))) ?>"><strong><?= e(trim(((string) ($a['tag'] ?? '')) . ' ' . ((string) ($a['name'] ?? '')))) ?: '(untagged)' ?></strong></a></td>
                <td><?= e((string) $a['type_name']) ?></td>
                <td class="small"><?= e((string) $a['sex']) ?></td>
                <td class="muted small"><?= e((string) ($a['breed'] ?: '—')) ?></td>
                <td class="num"><?= $a['weight'] !== null ? e(number_format((float) $a['weight'], 1)) . ' kg' : '—' ?></td>
                <td><span class="badge <?= $a['status'] === 'Active' ? 'green' : ($a['status'] === 'Sold' ? 'blue' : 'red') ?>"><?= e((string) $a['status']) ?></span></td>
                <td class="small muted"><?= e(Dates::forDisplay((string) $a['acquisition_date'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php $this->stop(); ?>
