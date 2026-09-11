<?php
/**
 * @var array<string,mixed> $a
 * @var array<int,array<string,mixed>> $health @var array<int,array<string,mixed>> $production
 * @var array<int,array<string,mixed>> $weights @var array<int,array<string,mixed>> $expenses
 * @var array<int,array<string,mixed>> $sales
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$id = rawurlencode((string) $a['id']);
$name = trim(((string) ($a['tag'] ?? '')) . ' ' . ((string) ($a['name'] ?? ''))) ?: '(untagged)';
$editable = $canManage && $a['status'] === 'Active';
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1"><?= e($name) ?></h1>
        <p class="lede"><?= e((string) $a['type_name']) ?> · <?= e((string) $a['sex']) ?>
            <?= $a['breed'] ? ' · ' . e((string) $a['breed']) : '' ?>
            <?= $a['parent_tag'] || $a['parent_name'] ? ' · dam/sire: ' . e(trim(((string) $a['parent_tag']) . ' ' . ((string) $a['parent_name']))) : '' ?></p>
    </div>
    <div class="row">
        <span class="badge <?= $a['status'] === 'Active' ? 'green' : ($a['status'] === 'Sold' ? 'blue' : 'red') ?>"><?= e((string) $a['status']) ?></span>
        <?php if ($canManage): ?><a class="btn secondary" href="<?= e(url('livestock/animals/' . $id . '/edit')) ?>">Edit</a><?php endif; ?>
    </div>
</div>

<div class="grid cols-4 mb-24px">
    <div class="stat"><div class="label">Current weight</div><div class="value" style="font-size:1.1rem"><?= $a['weight'] !== null ? e(number_format((float) $a['weight'], 1)) . ' kg' : '—' ?></div></div>
    <div class="stat"><div class="label">Age</div><div class="value" style="font-size:1.1rem"><?php
        if ($a['birth_date']) { $days = (int) floor((time() - strtotime((string) $a['birth_date'] . ' UTC')) / 86400); echo e($days > 730 ? round($days / 365, 1) . ' yr' : round($days / 30) . ' mo'); } else { echo '—'; }
    ?></div></div>
    <div class="stat"><div class="label">Health records</div><div class="value" style="font-size:1.1rem"><?= count($health) ?></div></div>
    <div class="stat"><div class="label">Acquisition cost</div><div class="value" style="font-size:1.1rem"><?= $a['acquisition_cost'] !== null ? e(Money::compact((float) $a['acquisition_cost'])) : '—' ?></div></div>
</div>

<?php if ($a['notes']): ?><p class="mb-16px muted"><?= nl2br(e((string) $a['notes'])) ?></p><?php endif; ?>

<?php
/** Renders one event section: heading, optional add form (as <details>), table. */
$section = function (string $heading, array $rows, array $cols, ?string $addKind, callable $addFields) use ($id, $editable) {
    ?>
    <div class="card mb-16px">
        <div class="card-head">
            <h2 class="h2"><?= e($heading) ?></h2>
            <?php if ($editable && $addKind !== null): ?>
                <details class="chip-select">
                    <summary>+ Add</summary>
                    <div class="menu" style="min-width:320px;padding:14px">
                        <form method="post" action="<?= e(url('livestock/animals/' . $id . '/events/' . $addKind)) ?>" class="stack" style="--stack-gap:10px">
                            <?= csrf_field() ?>
                            <?php $addFields(); ?>
                            <button class="btn sm" type="submit">Save record</button>
                        </form>
                    </div>
                </details>
            <?php endif; ?>
        </div>
        <div class="table-wrap" style="border:0">
            <table class="data">
                <thead><tr><?php foreach ($cols as $c): ?><th class="<?= $c['num'] ?? false ? 'num' : '' ?>"><?= e($c['label']) ?></th><?php endforeach; if ($editable): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php if ($rows === []): ?>
                    <tr><td colspan="<?= count($cols) + ($editable ? 1 : 0) ?>" class="muted">None recorded.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <?php foreach ($cols as $c): ?><td class="<?= $c['num'] ?? false ? 'num' : '' ?>"><?= $c['render']($r) ?></td><?php endforeach; ?>
                        <?php if ($editable): ?>
                            <td class="num">
                                <form method="post" action="<?= e(url('livestock/animals/' . $id . '/events/' . ($addKind ?? 'sale') . '/' . rawurlencode((string) $r['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this record?')">
                                    <?= csrf_field() ?><button class="btn sm ghost danger" type="submit">✕</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
};

$dt = static fn (string $k) => static fn ($r) => e(Dates::forDisplay((string) $r[$k]));
$txt = static fn (string $k) => static fn ($r) => e((string) ($r[$k] ?? '—'));
$mon = static fn (string $k) => static fn ($r) => e(Money::format((float) ($r[$k] ?? 0)));
?>

<?php $section('Health', $health, [
    ['label' => 'Date', 'render' => $dt('date')],
    ['label' => 'Type', 'render' => $txt('type')],
    ['label' => 'Description', 'render' => $txt('description')],
    ['label' => 'Vet', 'render' => $txt('veterinarian')],
    ['label' => 'Next due', 'render' => static fn ($r) => $r['next_due_date'] ? e(Dates::forDisplay((string) $r['next_due_date'])) : '—'],
    ['label' => 'Cost', 'num' => true, 'render' => $mon('cost')],
], 'health', function (): void { ?>
    <div class="field"><label class="small">Type</label><input class="input" name="type" list="hl_types" required><datalist id="hl_types"><option value="Vaccination"><option value="Treatment"><option value="Deworming"><option value="Check-up"></datalist></div>
    <div class="field"><label class="small">Description</label><input class="input" name="description" required></div>
    <div class="field"><label class="small">Vet (optional)</label><input class="input" name="veterinarian"></div>
    <div class="row" style="gap:8px">
        <div class="field flex-1"><label class="small">Date</label><input class="input" type="date" name="date" required value="<?= e(date('Y-m-d')) ?>"></div>
        <div class="field flex-1"><label class="small">Next due</label><input class="input" type="date" name="next_due_date"></div>
    </div>
    <div class="field"><label class="small">Cost</label><input class="input" type="number" step="any" name="cost" value="0"></div>
<?php }); ?>

<?php $section('Production', $production, [
    ['label' => 'Date', 'render' => $dt('date')],
    ['label' => 'Type', 'render' => $txt('type')],
    ['label' => 'Qty', 'num' => true, 'render' => static fn ($r) => e(rtrim(rtrim((string) $r['quantity'], '0'), '.')) . ' ' . e((string) $r['unit'])],
    ['label' => 'Value', 'num' => true, 'render' => static fn ($r) => $r['total_value'] !== null ? e(Money::format((float) $r['total_value'])) : '—'],
], 'production', function (): void { ?>
    <div class="field"><label class="small">Type</label><input class="input" name="type" list="pr_types" required><datalist id="pr_types"><option value="Milk"><option value="Eggs"><option value="Wool"><option value="Manure"></datalist></div>
    <div class="row" style="gap:8px">
        <div class="field flex-1"><label class="small">Quantity</label><input class="input" type="number" step="any" name="quantity" required></div>
        <div class="field flex-1"><label class="small">Unit</label><input class="input" name="unit" required placeholder="litres, trays"></div>
    </div>
    <div class="row" style="gap:8px">
        <div class="field flex-1"><label class="small">Date</label><input class="input" type="date" name="date" required value="<?= e(date('Y-m-d')) ?>"></div>
        <div class="field flex-1"><label class="small">Price / unit</label><input class="input" type="number" step="any" name="price_per_unit"></div>
    </div>
<?php }); ?>

<?php $section('Weight history', $weights, [
    ['label' => 'Date', 'render' => $dt('date')],
    ['label' => 'Weight', 'num' => true, 'render' => static fn ($r) => e(number_format((float) $r['weight'], 1)) . ' ' . e((string) $r['unit'])],
    ['label' => 'Notes', 'render' => $txt('notes')],
], 'weight', function (): void { ?>
    <div class="row" style="gap:8px">
        <div class="field flex-1"><label class="small">Weight</label><input class="input" type="number" step="any" name="weight" required></div>
        <div class="field" style="width:90px"><label class="small">Unit</label><select class="select" name="unit"><option>kg</option><option>lb</option></select></div>
    </div>
    <div class="field"><label class="small">Date</label><input class="input" type="date" name="date" required value="<?= e(date('Y-m-d')) ?>"></div>
<?php }); ?>

<?php $section('Expenses', $expenses, [
    ['label' => 'Date', 'render' => $dt('date')],
    ['label' => 'Category', 'render' => $txt('category')],
    ['label' => 'Description', 'render' => $txt('description')],
    ['label' => 'Amount', 'num' => true, 'render' => $mon('amount')],
], 'expense', function (): void { ?>
    <div class="field"><label class="small">Category</label><input class="input" name="category" list="ex_cats" required><datalist id="ex_cats"><option value="Feed"><option value="Medicine"><option value="Housing"><option value="Transport"></datalist></div>
    <div class="field"><label class="small">Description</label><input class="input" name="description" required></div>
    <div class="row" style="gap:8px">
        <div class="field flex-1"><label class="small">Amount</label><input class="input" type="number" step="any" name="amount" required></div>
        <div class="field flex-1"><label class="small">Date</label><input class="input" type="date" name="date" required value="<?= e(date('Y-m-d')) ?>"></div>
    </div>
<?php }); ?>

<?php $section('Sales', $sales, [
    ['label' => 'Date', 'render' => static fn ($r) => e(Dates::forDisplay((string) $r['sale_date']))],
    ['label' => 'Qty', 'num' => true, 'render' => $txt('quantity')],
    ['label' => 'Weight', 'num' => true, 'render' => static fn ($r) => $r['weight_at_sale'] !== null ? e(number_format((float) $r['weight_at_sale'], 1)) . ' kg' : '—'],
    ['label' => 'Buyer', 'render' => $txt('buyer')],
    ['label' => 'Total', 'num' => true, 'render' => $mon('total_amount')],
], null, static function (): void {}); ?>

<?php if ($editable): ?>
<div class="card">
    <div class="card-head"><h2 class="h2">Sell this animal</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('livestock/animals/' . $id . '/sell')) ?>" class="grid cols-2" style="gap:12px">
            <?= csrf_field() ?>
            <?= $this->partial('partials/field', ['name' => 'sale_date', 'label' => 'Sale date', 'type' => 'date', 'required' => true, 'value' => date('Y-m-d')]) ?>
            <?= $this->partial('partials/field', ['name' => 'total_amount', 'label' => 'Total amount', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal', 'required' => true]) ?>
            <?= $this->partial('partials/field', ['name' => 'weight_at_sale', 'label' => 'Weight at sale (kg)', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal']) ?>
            <?= $this->partial('partials/field', ['name' => 'price_per_kg', 'label' => 'Price per kg', 'type' => 'number', 'step' => 'any', 'inputmode' => 'decimal']) ?>
            <?= $this->partial('partials/field', ['name' => 'buyer', 'label' => 'Buyer']) ?>
            <div style="grid-column:1/-1"><button class="btn" type="submit">Record sale &amp; mark Sold</button></div>
        </form>
        <p class="hint mt-8px">Creates an income transaction in Finance and sets the animal’s status to Sold.</p>
    </div>
</div>
<?php endif; ?>
<?php $this->stop(); ?>
