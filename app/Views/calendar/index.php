<?php
/**
 * @var int $year @var int $month @var string $monthLabel
 * @var array<string,list<array{kind:string,label:string,crop:?string,field:?string}>> $days
 * @var int $prevYear @var int $prevMonth @var int $nextYear @var int $nextMonth
 * @var string $todayIso
 */
$this->layout('layouts/app');

$kindBadge = ['planting' => 'green', 'harvest' => 'amber', 'stage' => 'blue', 'activity' => ''];
$kindLabel = ['planting' => 'Planting', 'harvest' => 'Harvest', 'stage' => 'Stage', 'activity' => 'Activity'];

$firstOfMonth = sprintf('%04d-%02d-01', $year, $month);
$firstTs = (int) strtotime($firstOfMonth);
$daysInMonth = (int) date('t', $firstTs);
$leadingBlanks = ((int) date('N', $firstTs)) - 1; // Monday-start week

$totalEvents = array_sum(array_map('count', $days));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Calendar</h1>
        <p class="lede"><?= $totalEvents ?> event<?= $totalEvents === 1 ? '' : 's' ?> in <?= e($monthLabel) ?></p>
    </div>
    <div class="row" style="gap:8px">
        <a class="btn ghost sm" href="<?= e(url('calendar') . '?year=' . $prevYear . '&month=' . $prevMonth) ?>">
            <?= $this->partial('partials/icon', ['name' => 'chevron-left', 'class' => 'ico ico-sm']) ?> Prev
        </a>
        <span class="h3" style="min-width:140px;text-align:center"><?= e($monthLabel) ?></span>
        <a class="btn ghost sm" href="<?= e(url('calendar') . '?year=' . $nextYear . '&month=' . $nextMonth) ?>">
            Next <?= $this->partial('partials/icon', ['name' => 'chevron-right', 'class' => 'ico ico-sm']) ?>
        </a>
    </div>
</div>

<div class="row" style="gap:12px;flex-wrap:wrap;margin-bottom:12px">
    <?php foreach ($kindLabel as $kind => $label): ?>
        <span class="badge <?= $kindBadge[$kind] ?>"><?= e($label) ?></span>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:24px">
    <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd): ?>
        <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text-faint);text-align:center;padding:4px 0"><?= $wd ?></div>
    <?php endforeach; ?>

    <?php for ($i = 0; $i < $leadingBlanks; $i++): ?>
        <div></div>
    <?php endfor; ?>

    <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $iso = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $events = $days[$iso] ?? [];
        $isToday = $iso === $todayIso;
    ?>
        <div style="min-height:84px;border:1px solid var(--line);border-radius:12px;padding:6px;background:<?= $isToday ? 'var(--teal-pale)' : 'var(--surface)' ?>">
            <p style="font-size:.75rem;font-weight:<?= $isToday ? '900' : '700' ?>;color:<?= $isToday ? 'var(--teal)' : 'var(--text-faint)' ?>;margin-bottom:4px"><?= $d ?></p>
            <div class="stack" style="--stack-gap:2px">
                <?php foreach (array_slice($events, 0, 3) as $ev): ?>
                    <span class="badge <?= $kindBadge[$ev['kind']] ?? '' ?>" style="font-size:.6rem;padding:2px 6px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <?= e($ev['crop'] ?? $ev['label']) ?>
                    </span>
                <?php endforeach; ?>
                <?php if (count($events) > 3): ?>
                    <span class="small muted" style="font-size:.6rem">+<?= count($events) - 3 ?> more</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endfor; ?>
</div>

<div class="h3 mb-16px">This month</div>
<?php if ($days === []): ?>
    <div class="empty"><div class="h3">No events this month</div><p>Plantings, expected harvests, timeline stages and logged activities will show up here.</p></div>
<?php else: ?>
    <div class="stack" style="--stack-gap:8px">
        <?php foreach ($days as $iso => $events): ?>
            <div class="card"><div class="card-body">
                <p style="font-size:.75rem;font-weight:800;color:var(--text-faint);margin-bottom:8px"><?= e(date('l, j F', strtotime($iso))) ?></p>
                <div class="stack" style="--stack-gap:6px">
                    <?php foreach ($events as $ev): ?>
                        <div class="row spread" style="gap:8px">
                            <div class="row" style="gap:8px">
                                <span class="badge <?= $kindBadge[$ev['kind']] ?? '' ?>"><?= e($kindLabel[$ev['kind']] ?? ucfirst($ev['kind'])) ?></span>
                                <span class="small"><?= e($ev['label']) ?><?= $ev['crop'] !== null ? ' — ' . e($ev['crop']) : '' ?></span>
                            </div>
                            <?php if ($ev['field'] !== null): ?><span class="small muted"><?= e($ev['field']) ?></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
