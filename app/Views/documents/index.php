<?php
/**
 * @var array<int,array<string,mixed>> $documents
 * @var list<string> $types @var string $type
 * @var bool $canManage
 * @var array<string,int> $counts
 * @var int $total
 */
$this->layout('layouts/app');
use App\Support\Dates;
$label = static fn (string $t): string => ucfirst($t);
$typeIcon = [
    'deed' => 'file-text', 'certificate' => 'shield-check', 'receipt' => 'wallet',
    'contract' => 'file-text', 'photo' => 'map', 'other' => 'file-text',
];
$fmtSize = static function (?int $b): string {
    if ($b === null) return '—';
    if ($b < 1024) return $b . ' B';
    if ($b < 1024 * 1024) return round($b / 1024, 1) . ' KB';
    return round($b / (1024 * 1024), 1) . ' MB';
};
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Documents</h1>
        <p class="lede"><?= count($documents) ?> file<?= count($documents) === 1 ? '' : 's' ?> — receipts, certificates, contracts, photos</p>
    </div>
</div>

<?php if ($canManage): ?>
<div class="card mb-24px">
    <div class="card-head"><h2 class="h2">Upload a document</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('documents')) ?>" enctype="multipart/form-data" class="grid cols-2" style="gap:14px">
            <?= csrf_field() ?>
            <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Document name', 'required' => true]) ?>
            <div class="field">
                <label for="f_type">Type</label>
                <select class="select" id="f_type" name="type">
                    <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e($label($t)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="f_document">File <span aria-hidden="true" style="color:var(--red)">*</span></label>
                <input class="input" type="file" id="f_document" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required
                       <?= error_for('document') ? 'aria-invalid="true"' : '' ?>>
                <p class="hint">PDF, JPG, PNG or WEBP, up to 8 MB.</p>
                <?php if ($er = error_for('document')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label for="f_notes">Notes (optional)</label>
                <textarea class="textarea" id="f_notes" name="notes" rows="2"></textarea>
            </div>
            <div style="grid-column:1/-1"><button type="submit" class="btn">Upload</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:16px">
    <a href="<?= e(url('documents')) ?>" class="row" style="justify-content:center;min-height:56px;border-radius:16px;font-weight:700;font-size:.875rem;<?= $type === '' ? 'background:var(--sky-600);color:#fff' : 'background:var(--surface);border:1px solid var(--line);color:var(--text-soft)' ?>">
        All (<?= $total ?>)
    </a>
    <?php foreach ($types as $t): ?>
        <a href="<?= e(url('documents?type=' . rawurlencode($t))) ?>" class="row" style="justify-content:center;gap:6px;min-height:56px;border-radius:16px;font-weight:700;font-size:.875rem;<?= $type === $t ? 'background:var(--sky-600);color:#fff' : 'background:var(--surface);border:1px solid var(--line);color:var(--text-soft)' ?>">
            <?= $this->partial('partials/icon', ['name' => $typeIcon[$t] ?? 'file-text', 'class' => 'ico ico-sm']) ?>
            <span class="nowrap"><?= e($label($t)) ?> (<?= $counts[$t] ?? 0 ?>)</span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($documents === []): ?>
    <div class="empty"><div class="h3">No documents</div><p>Upload receipts, certificates, contracts and evidence photos here.</p></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Name</th><th>Type</th><th class="num">Size</th><th>Uploaded</th><th>By</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($documents as $d): ?>
            <tr>
                <td><strong><?= e((string) $d['name']) ?></strong><?php if ($d['notes']): ?><div class="small muted"><?= e((string) $d['notes']) ?></div><?php endif; ?></td>
                <td><span class="badge"><?= e($label((string) $d['type'])) ?></span></td>
                <td class="num small"><?= e($fmtSize($d['size'] !== null ? (int) $d['size'] : null)) ?></td>
                <td class="small"><?= e(Dates::forDisplay((string) $d['uploaded_at'])) ?></td>
                <td class="small muted"><?= e((string) ($d['uploaded_by_name'] ?: '—')) ?></td>
                <td class="num nowrap">
                    <a class="btn sm ghost" href="<?= e(url('documents/' . rawurlencode((string) $d['id']) . '/download')) ?>">Download</a>
                    <?php if ($canManage): ?>
                        <form method="post" action="<?= e(url('documents/' . rawurlencode((string) $d['id']) . '/delete')) ?>"
                              style="display:inline" onsubmit="return confirm('Delete this document?')">
                            <?= csrf_field() ?>
                            <button class="btn sm ghost danger" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php $this->stop(); ?>
