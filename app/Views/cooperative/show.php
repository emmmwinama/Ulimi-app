<?php
/**
 * @var array<string,mixed> $coop
 * @var string $role
 * @var bool $canManage
 * @var array<int,array<string,mixed>> $members
 * @var array<int,array<string,mixed>> $contributions
 * @var array<int,array<string,mixed>> $sales
 * @var array<int,array<string,mixed>> $inventoryRollup
 * @var array<int,array<string,mixed>> $productionRollup
 * @var list<string> $contributionTypes
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
use App\Core\FarmContext;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$myFarmId = FarmContext::current()->farmId();
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) $coop['name']) ?></h1>
        <p class="lede"><?= e((string) ($coop['region'] ?? '')) ?> · <?= count($members) ?> member farm<?= count($members) === 1 ? '' : 's' ?> · your role: <?= e($label($role)) ?></p>
    </div>
    <a class="btn ghost" href="<?= e(url('cooperatives')) ?>">← Cooperatives</a>
</div>

<?php if ($canManage): ?>
    <div class="alert info mb-16px">
        <?= $this->partial('partials/icon', ['name' => 'info', 'class' => 'ico']) ?>
        <div>Join code: <strong><?= e((string) $coop['join_code']) ?></strong> — share this with other farms so they can join.</div>
    </div>
<?php endif; ?>

<div class="grid cols-2 mb-24px" style="gap:20px;align-items:start">
    <div class="card">
        <div class="card-head"><h2 class="h2">Group inventory (by category)</h2></div>
        <div class="card-body">
            <?php if ($inventoryRollup === []): ?>
                <p class="muted small">No stock recorded across member farms yet.</p>
            <?php else: ?>
                <div class="table-wrap" style="border:0"><table class="data">
                    <thead><tr><th>Category</th><th class="num">Items</th><th class="num">Total qty</th></tr></thead>
                    <tbody>
                    <?php foreach ($inventoryRollup as $r): ?>
                        <tr><td><?= e($label((string) $r['category'])) ?></td><td class="num"><?= (int) $r['item_count'] ?></td><td class="num"><?= e(rtrim(rtrim(number_format((float) $r['total_quantity'], 3), '0'), '.')) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="h2">Group production (last 12 months)</h2></div>
        <div class="card-body">
            <?php if ($productionRollup === []): ?>
                <p class="muted small">No harvests recorded across member farms yet.</p>
            <?php else: ?>
                <div class="table-wrap" style="border:0"><table class="data">
                    <thead><tr><th>Crop</th><th class="num">Harvests</th><th class="num">Total kg</th></tr></thead>
                    <tbody>
                    <?php foreach ($productionRollup as $r): ?>
                        <tr><td><?= e((string) $r['crop_name']) ?></td><td class="num"><?= (int) $r['harvest_count'] ?></td><td class="num"><?= number_format((float) $r['total_kg']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-24px">
    <div class="card-head"><h2 class="h2">Members</h2></div>
    <div class="table-wrap" style="border:0"><table class="data">
        <thead><tr><th>Farm</th><th>Location</th><th>Role</th><th>Joined</th><?php if ($canManage): ?><th class="num">Actions</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= e((string) $m['farm_name']) ?></td>
                <td class="small muted"><?= e((string) ($m['farm_location'] ?? '—')) ?></td>
                <td><span class="badge"><?= e($label((string) $m['role'])) ?></span></td>
                <td class="small"><?= e(Dates::forDisplay((string) $m['joined_at'])) ?></td>
                <?php if ($canManage): ?>
                    <td class="num">
                        <?php if ((string) $m['farm_id'] !== $myFarmId): ?>
                            <form method="post" action="<?= e(url('cooperatives/' . rawurlencode((string) $coop['id']) . '/members/' . rawurlencode((string) $m['farm_id']) . '/remove')) ?>" onsubmit="return confirm('Remove this farm from the cooperative?')">
                                <?= csrf_field() ?>
                                <button class="btn sm ghost danger" type="submit">Remove</button>
                            </form>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<div class="grid cols-2" style="gap:20px;align-items:start">
    <div class="card">
        <div class="card-head"><h2 class="h2">Contributions</h2></div>
        <div class="card-body">
            <?php if ($contributions === []): ?>
                <p class="muted small">No contributions recorded yet.</p>
            <?php else: ?>
                <div class="stack mb-16px" style="--stack-gap:6px">
                    <?php foreach ($contributions as $c): ?>
                        <div class="spread small" style="border-bottom:1px solid var(--line);padding-bottom:6px">
                            <span><?= e((string) $c['farm_name']) ?> — <?= e($label((string) $c['type'])) ?></span>
                            <span><?= e(Money::format((float) $c['amount'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('cooperatives/' . rawurlencode((string) $coop['id']) . '/contributions')) ?>" class="stack" style="--stack-gap:10px;border-top:1px solid var(--line);padding-top:12px">
                    <?= csrf_field() ?>
                    <p class="eyebrow">Record a contribution</p>
                    <div class="field">
                        <label for="f_cc_farm">Farm</label>
                        <select class="select" id="f_cc_farm" name="farm_id">
                            <?php foreach ($members as $m): ?><option value="<?= e((string) $m['farm_id']) ?>"><?= e((string) $m['farm_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid cols-2">
                        <?= $this->partial('partials/field', ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
                        <?= $this->partial('partials/field', ['name' => 'date', 'label' => 'Date', 'type' => 'date', 'required' => true, 'value' => date('Y-m-d')]) ?>
                    </div>
                    <div class="field">
                        <label for="f_cc_type">Type</label>
                        <select class="select" id="f_cc_type" name="type">
                            <?php foreach ($contributionTypes as $t): ?><option value="<?= e($t) ?>"><?= e($label($t)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn sm" type="submit">Record</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2 class="h2">Collective sales</h2></div>
        <div class="card-body">
            <?php if ($sales === []): ?>
                <p class="muted small">No collective sales recorded yet.</p>
            <?php else: ?>
                <div class="stack mb-16px" style="--stack-gap:6px">
                    <?php foreach ($sales as $s): ?>
                        <div class="spread small" style="border-bottom:1px solid var(--line);padding-bottom:6px">
                            <span><?= e((string) $s['crop_name']) ?> — <?= e(Dates::forDisplay((string) $s['sale_date'])) ?></span>
                            <span><?= e(Money::format((float) $s['total_amount'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('cooperatives/' . rawurlencode((string) $coop['id']) . '/sales')) ?>" class="stack" style="--stack-gap:10px;border-top:1px solid var(--line);padding-top:12px">
                    <?= csrf_field() ?>
                    <p class="eyebrow">Record a collective sale</p>
                    <?= $this->partial('partials/field', ['name' => 'crop_name', 'label' => 'Crop', 'required' => true]) ?>
                    <div class="grid cols-2">
                        <?= $this->partial('partials/field', ['name' => 'total_quantity', 'label' => 'Total quantity', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
                        <?= $this->partial('partials/field', ['name' => 'unit', 'label' => 'Unit', 'value' => 'kg']) ?>
                    </div>
                    <div class="grid cols-2">
                        <?= $this->partial('partials/field', ['name' => 'price_per_unit', 'label' => 'Price per unit', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
                        <?= $this->partial('partials/field', ['name' => 'sale_date', 'label' => 'Sale date', 'type' => 'date', 'required' => true, 'value' => date('Y-m-d')]) ?>
                    </div>
                    <?= $this->partial('partials/field', ['name' => 'buyer_name', 'label' => 'Buyer (optional)']) ?>

                    <p class="eyebrow mt-8px">Split between members (optional — quantity per farm)</p>
                    <?php foreach ($members as $m): ?>
                        <div class="row" style="gap:8px;align-items:flex-end">
                            <input type="hidden" name="split_farm_id[]" value="<?= e((string) $m['farm_id']) ?>">
                            <div class="field flex-1"><label class="small"><?= e((string) $m['farm_name']) ?> — qty</label><input class="input" type="number" step="any" name="split_quantity[]"></div>
                            <div class="field flex-1"><label class="small">amount</label><input class="input" type="number" step="any" name="split_amount[]"></div>
                        </div>
                    <?php endforeach; ?>
                    <button class="btn sm" type="submit">Record sale</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
