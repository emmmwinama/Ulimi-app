<?php
/**
 * @var string $packType @var string $packLabel
 * @var array<string,mixed> $farm
 * @var array<string,array{label:string,columns:array<string,string>,rows:array<int,array<string,mixed>>}> $sections
 * @var string $season @var list<string> $seasons @var string $generatedAt
 */
$this->layout('layouts/print');
$farmName = (string) ($farm['name'] ?? '');
$dateRange = $season !== '' ? $season : 'All seasons';
$purposes = [
    'loan' => 'Loan / credit application supporting evidence',
    'buyer' => 'Buyer traceability and production evidence',
    'audit' => 'Internal or third-party audit file',
    'insurance' => 'Insurance claim or underwriting evidence',
];
$purpose = $purposes[$packType] ?? '';
?>
<?php $this->start('content'); ?>

<form method="get" action="<?= e(url('reports/pack/' . $packType)) ?>" class="no-print mb-16px">
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:260px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php foreach ($sections as $sec): ?>
    <div class="doc-section">
        <h2><?= e($sec['label']) ?></h2>
        <?php if ($sec['rows'] === []): ?>
            <p class="muted small">No records in this period.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><?php foreach ($sec['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?></tr></thead>
                    <tbody>
                    <?php foreach ($sec['rows'] as $row): ?>
                        <tr>
                            <?php foreach (array_keys($sec['columns']) as $col): ?>
                                <td class="small"><?= e(is_bool($row[$col] ?? null) ? (($row[$col]) ? 'Yes' : 'No') : (string) ($row[$col] ?? '—')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
<?php $this->stop(); ?>
