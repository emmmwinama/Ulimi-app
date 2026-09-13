<?php
/**
 * @var string $packType @var string $packLabel @var string $purpose
 * @var array<string,mixed> $farm @var string $farmName @var string $dateRange
 * @var array<string,array{label:string,columns:array<string,string>,rows:array<int,array<string,mixed>>}> $sections
 * @var string $season @var list<string> $seasons @var string $generatedAt
 * @var bool $public
 * @var array<int,array<string,mixed>> $shareLinks
 * @var bool $canShare
 */
$this->layout('layouts/print');
$public = $public ?? false;
$shareLinks = $shareLinks ?? [];
$canShare = $canShare ?? false;
?>
<?php $this->start('content'); ?>

<?php if (!$public): ?>
<form method="get" action="<?= e(url('reports/pack/' . $packType)) ?>" class="no-print mb-16px">
    <select class="select" name="season" onchange="this.form.submit()" style="max-width:260px">
        <option value="">All seasons</option>
        <?php foreach ($seasons as $s): ?>
            <option value="<?= e($s) ?>" <?= $s === $season ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php if ($canShare): ?>
<div class="card no-print mb-16px">
    <div class="card-head"><h2 class="h2">Share this pack</h2></div>
    <div class="card-body">
        <p class="small muted mb-12px">Generate a link a lender, buyer or NGO can open without an Ulimi account. It stops working after 14 days or as soon as you revoke it.</p>
        <form method="post" action="<?= e(url('reports/pack/' . $packType . '/share')) ?>" class="mb-12px">
            <?= csrf_field() ?>
            <button type="submit" class="btn sm">Create share link</button>
        </form>
        <?php if ($shareLinks !== []): ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Created</th><th>Expires</th><th>Views</th><th>Status</th><th class="num">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($shareLinks as $l): $revoked = !empty($l['revoked_at']); $expired = strtotime((string) $l['expires_at']) < time(); ?>
                        <tr>
                            <td class="small"><?= e((string) $l['created_at']) ?></td>
                            <td class="small"><?= e((string) $l['expires_at']) ?></td>
                            <td class="num"><?= (int) $l['view_count'] ?></td>
                            <td><span class="badge <?= $revoked || $expired ? '' : 'green' ?>"><?= $revoked ? 'Revoked' : ($expired ? 'Expired' : 'Active') ?></span></td>
                            <td class="num">
                                <?php if (!$revoked && !$expired): ?>
                                    <form method="post" action="<?= e(url('reports/share-links/' . rawurlencode((string) $l['id']) . '/revoke')) ?>" onsubmit="return confirm('Revoke this link? It will stop working immediately.')">
                                        <?= csrf_field() ?>
                                        <button class="btn sm ghost danger" type="submit">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

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
