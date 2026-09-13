<?php
/**
 * @var array<string,mixed> $e
 * @var array<int,array<string,mixed>> $logs
 * @var list<string> $categories @var list<string> $statuses
 * @var bool $canManage
 */
$this->layout('layouts/app');
use App\Support\Dates;
use App\Support\Money;
$label = static fn (string $s): string => ucwords(str_replace('_', ' ', $s));
$statusBadge = ['active' => 'green', 'under_repair' => 'amber', 'retired' => ''];
$totalCost = array_sum(array_map(static fn ($l) => (float) $l['cost'], $logs));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1"><?= e((string) $e['name']) ?></h1>
        <p class="lede"><?= e($label((string) $e['category'])) ?> · <span class="badge <?= $statusBadge[$e['status']] ?? '' ?>"><?= e($label((string) $e['status'])) ?></span></p>
    </div>
    <?php if ($canManage): ?>
        <div class="row" style="gap:8px">
            <a class="btn ghost" href="#edit-equipment">Edit</a>
            <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $e['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this equipment record? Its maintenance history will be deleted too.')">
                <?= csrf_field() ?>
                <button class="btn ghost danger" type="submit">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="grid cols-4 mb-16px">
    <div class="stat"><div class="label">Acquired</div><div class="value" style="font-size:1.1rem"><?= $e['acquisition_date'] ? e(Dates::forDisplay((string) $e['acquisition_date'])) : '—' ?></div></div>
    <div class="stat"><div class="label">Acquisition cost</div><div class="value" style="font-size:1.1rem"><?= $e['acquisition_cost'] !== null ? e(Money::format((float) $e['acquisition_cost'])) : '—' ?></div></div>
    <div class="stat"><div class="label">Maintenance records</div><div class="value" style="font-size:1.1rem"><?= count($logs) ?></div></div>
    <div class="stat"><div class="label">Total maintenance cost</div><div class="value" style="font-size:1.1rem"><?= e(Money::format($totalCost)) ?></div></div>
</div>

<?php if (!empty($e['notes'])): ?>
    <div class="card mb-16px"><div class="card-body"><p><?= nl2br(e((string) $e['notes'])) ?></p></div></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <h2 class="h2">Maintenance history</h2>
        <?php if ($canManage): ?>
            <details class="chip-select">
                <summary>+ Add</summary>
                <div class="menu" style="min-width:320px;padding:14px">
                    <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $e['id']) . '/logs')) ?>" class="stack" style="--stack-gap:10px">
                        <?= csrf_field() ?>
                        <div class="field"><label class="small">What was done</label><input class="input" name="description" required></div>
                        <div class="row" style="gap:8px">
                            <div class="field flex-1"><label class="small">Date</label><input class="input" type="date" name="date" required value="<?= e(date('Y-m-d')) ?>"></div>
                            <div class="field flex-1"><label class="small">Cost</label><input class="input" type="number" step="any" name="cost" value="0"></div>
                        </div>
                        <div class="field"><label class="small">Hours used (optional)</label><input class="input" type="number" step="any" name="hours_used"></div>
                        <div class="field"><label class="small">Notes (optional)</label><input class="input" name="notes"></div>
                        <button class="btn sm" type="submit">Save record</button>
                    </form>
                </div>
            </details>
        <?php endif; ?>
    </div>
    <div class="table-wrap" style="border:0">
        <table class="data">
            <thead><tr><th>Date</th><th>Description</th><th class="num">Hours</th><th class="num">Cost</th><th>Notes</th><?php if ($canManage): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="muted">No maintenance recorded yet.</td></tr>
            <?php else: foreach ($logs as $l): ?>
                <tr>
                    <td><?= e(Dates::forDisplay((string) $l['date'])) ?></td>
                    <td><?= e((string) $l['description']) ?></td>
                    <td class="num"><?= $l['hours_used'] !== null ? e((string) $l['hours_used']) : '—' ?></td>
                    <td class="num"><?= e(Money::format((float) $l['cost'])) ?></td>
                    <td class="small muted"><?= e((string) ($l['notes'] ?? '—')) ?></td>
                    <?php if ($canManage): ?>
                        <td class="num">
                            <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $e['id']) . '/logs/' . rawurlencode((string) $l['id']) . '/delete')) ?>" onsubmit="return confirm('Delete this record?')">
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

<?php if ($canManage): ?>
    <div class="slide-over" id="edit-equipment">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Edit equipment</h2>
                    <p class="small muted mt-8px"><?= e((string) $e['name']) ?></p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('equipment/' . rawurlencode((string) $e['id']))) ?>" style="display:contents">
                <?= csrf_field() ?>
                <?= method_field('PUT') ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/equipment/equipment', ['item' => $e, 'categories' => $categories, 'statuses' => $statuses]) ?>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Save changes</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
