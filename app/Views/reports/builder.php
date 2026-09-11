<?php
/**
 * @var array<string,array{label:string,columns:array<string,string>}> $available
 * @var list<string> $selected @var string $season @var string $from @var string $to
 * @var list<string> $seasons
 * @var array<string,array{label:string,columns:array<string,string>,rows:array<int,array<string,mixed>>}> $sections
 */
$this->layout('layouts/app');
$qs = static function (array $extra = []) use ($selected, $season, $from, $to) {
    $params = array_merge(['sections' => $selected, 'season' => $season, 'from' => $from, 'to' => $to], $extra);
    return http_build_query($params);
};
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Report builder</h1>
        <p class="lede">Pick sections and filters, preview, then export each as CSV.</p>
    </div>
</div>

<div class="card mb-24">
    <div class="card-body">
        <form method="get" action="<?= e(url('reports/builder')) ?>">
            <div class="grid cols-4 mb-16">
                <?php foreach ($available as $key => $meta): ?>
                    <label class="checkline">
                        <input type="checkbox" name="sections[]" value="<?= e($key) ?>" <?= in_array($key, $selected, true) ? 'checked' : '' ?>>
                        <span><?= e($meta['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="grid cols-3" style="gap:12px">
                <div class="field">
                    <label class="small">Season</label>
                    <select class="select" name="season">
                        <option value="">All</option>
                        <?php foreach ($seasons as $s): ?><option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label class="small">From date</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
                <div class="field"><label class="small">To date</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div>
            </div>
            <button type="submit" class="btn mt-16">Build report</button>
        </form>
    </div>
</div>

<?php if ($sections === []): ?>
    <div class="empty"><div class="h3">Choose sections above</div><p>Select one or more record types and build to see a preview here.</p></div>
<?php else: foreach ($sections as $key => $sec): ?>
    <div class="card mb-16">
        <div class="card-head">
            <h2 class="h2"><?= e($sec['label']) ?> <span class="muted small">(<?= count($sec['rows']) ?>)</span></h2>
            <a class="btn sm secondary" href="<?= e(url('reports/export/' . $key . '?' . $qs())) ?>">Export CSV</a>
        </div>
        <div class="table-wrap" style="border:0">
            <?php if ($sec['rows'] === []): ?>
                <p class="muted small" style="padding:16px">No rows.</p>
            <?php else: ?>
                <table class="data">
                    <thead><tr><?php foreach ($sec['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($sec['rows'], 0, 50) as $row): ?>
                        <tr><?php foreach (array_keys($sec['columns']) as $col): ?><td class="small"><?= e((string) ($row[$col] ?? '—')) ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($sec['rows']) > 50): ?><p class="hint" style="padding:0 16px 12px">Showing first 50 of <?= count($sec['rows']) ?> — export CSV for the full set.</p><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; endif; ?>
<?php $this->stop(); ?>
