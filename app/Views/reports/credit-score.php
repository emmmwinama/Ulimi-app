<?php
/**
 * @var array{score:int,grade:string,factors:list<array{key:string,label:string,weight:int,earned:int,detail:string}>} $result
 * @var array<int,array<string,mixed>> $history
 */
$this->layout('layouts/app');
use App\Support\Dates;
$gradeColour = ['A' => 'green', 'B' => 'green', 'C' => 'amber', 'D' => 'amber', 'E' => 'red'][$result['grade']] ?? '';
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Credit readiness</h1>
        <p class="lede">A transparent score computed from your own records — not a bureau score.</p>
    </div>
    <form method="post" action="<?= e(url('reports/credit-score/recompute')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn secondary">Recalculate &amp; save</button>
    </form>
</div>

<div class="grid cols-2 mb-24">
    <div class="card">
        <div class="card-body text-center" style="padding:32px">
            <div class="eyebrow">Score</div>
            <div style="font-size:3rem;font-weight:800;color:var(--navy);line-height:1"><?= e((string) $result['score']) ?></div>
            <div class="mt-8"><span class="badge <?= e($gradeColour) ?>" style="font-size:1rem;padding:6px 14px">Grade <?= e($result['grade']) ?></span></div>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><h2 class="h2">History</h2></div>
        <div class="card-body">
            <?php if ($history === []): ?>
                <p class="muted">No saved history yet — recalculate to start tracking.</p>
            <?php else: ?>
                <table class="data"><tbody>
                <?php foreach ($history as $h): ?>
                    <tr><td class="small"><?= e(Dates::forDisplay((string) $h['generated_at'], 'j M Y')) ?></td>
                        <td class="num"><?= e((string) $h['score']) ?></td>
                        <td><span class="badge"><?= e((string) $h['grade']) ?></span></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2 class="h2">Factors</h2></div>
    <div class="card-body stack">
        <?php foreach ($result['factors'] as $f): ?>
            <div>
                <div class="spread small mb-8">
                    <strong><?= e($f['label']) ?></strong>
                    <span class="muted"><?= e((string) $f['earned']) ?> / <?= e((string) $f['weight']) ?></span>
                </div>
                <div style="height:8px;background:var(--surface-2);border-radius:4px;overflow:hidden">
                    <div style="height:100%;width:<?= (int) round($f['earned'] / max(1, $f['weight']) * 100) ?>%;background:var(--blue)"></div>
                </div>
                <p class="small muted mt-8"><?= e($f['detail']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php $this->stop(); ?>
