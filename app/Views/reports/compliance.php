<?php
/**
 * @var list<array{area:string,present:bool,count:int,hint:string}> $checklist
 * @var list<array{crop_field_id:string,lot_id:string,crop:string,field:string,season:string,checks:array<string,bool>,score:int}> $lots
 */
$this->layout('layouts/app');
$present = count(array_filter($checklist, static fn ($c) => $c['present']));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Compliance</h1>
        <p class="lede"><?= $present ?> of <?= count($checklist) ?> record categories in use</p>
    </div>
</div>

<div class="card mb-24">
    <div class="card-head"><h2 class="h2">Evidence coverage</h2></div>
    <div class="card-body">
        <table class="data"><tbody>
        <?php foreach ($checklist as $c): ?>
            <tr>
                <td style="width:28px">
                    <?= $this->partial('partials/icon', ['name' => $c['present'] ? 'check-circle' : 'alert', 'class' => 'ico']) ?>
                </td>
                <td><strong><?= e($c['area']) ?></strong><div class="small muted"><?= e($c['hint']) ?></div></td>
                <td class="num"><span class="badge <?= $c['present'] ? 'green' : 'amber' ?>"><?= e((string) $c['count']) ?> record<?= $c['count'] === 1 ? '' : 's' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Lot traceability</h2></div>
    <div class="card-body">
        <?php if ($lots === []): ?>
            <p class="muted">No active plantings to trace yet.</p>
        <?php else: ?>
            <div class="table-wrap" style="border:0">
                <table class="data">
                    <thead><tr><th>Lot ID</th><th>Crop</th><th>Field</th><th>Season</th><th class="num">Buyer-ready</th></tr></thead>
                    <tbody>
                    <?php foreach ($lots as $l): ?>
                        <tr>
                            <td class="mono small"><?= e($l['lot_id']) ?></td>
                            <td><?= e($l['crop']) ?></td>
                            <td><?= e($l['field']) ?></td>
                            <td class="small"><?= e($l['season']) ?></td>
                            <td class="num">
                                <span class="badge <?= $l['score'] >= 80 ? 'green' : ($l['score'] >= 50 ? 'amber' : 'red') ?>"><?= e((string) $l['score']) ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="hint mt-8">Score = share of: planting record, field activities, input records, spray/pest record, harvest yield, and a linked sale.</p>
        <?php endif; ?>
    </div>
</div>
<?php $this->stop(); ?>
