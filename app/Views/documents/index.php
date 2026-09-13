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
    <?php if ($canManage): ?>
        <a class="btn" href="#upload-document"><?= $this->partial('partials/icon', ['name' => 'plus', 'class' => 'ico ico-sm']) ?> Upload a document</a>
    <?php endif; ?>
</div>

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
    <div class="grid cols-3">
        <?php foreach ($documents as $d): ?>
            <div class="card" style="display:flex;flex-direction:column">
                <div class="card-body" style="flex:1">
                    <div class="row" style="gap:10px;margin-bottom:12px">
                        <span class="icon-box teal">
                            <?= $this->partial('partials/icon', ['name' => $typeIcon[$d['type']] ?? 'file-text', 'class' => 'ico']) ?>
                        </span>
                        <div style="min-width:0">
                            <h3 class="h3" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e((string) $d['name']) ?></h3>
                            <p style="font-size:.75rem;color:var(--text-faint)"><span class="badge"><?= e($label((string) $d['type'])) ?></span></p>
                        </div>
                    </div>

                    <div class="grid cols-2" style="gap:8px">
                        <div class="mini-stat">
                            <p class="label">Size</p>
                            <p class="value"><?= e($fmtSize($d['size'] !== null ? (int) $d['size'] : null)) ?></p>
                        </div>
                        <div class="mini-stat">
                            <p class="label">Uploaded by</p>
                            <p class="value" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e((string) ($d['uploaded_by_name'] ?: '—')) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($d['notes'])): ?>
                        <p style="font-size:.75rem;font-style:italic;color:var(--text-faint);margin-top:8px"><?= e((string) $d['notes']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="spread card-foot">
                    <p style="font-size:.75rem;color:var(--text-faint)"><?= e(Dates::forDisplay((string) $d['uploaded_at'])) ?></p>
                    <div class="row" style="gap:4px">
                        <a href="<?= e(url('documents/' . rawurlencode((string) $d['id']) . '/download')) ?>" title="Download"
                           class="icon-box sm teal">
                            <?= $this->partial('partials/icon', ['name' => 'arrow-right', 'class' => 'ico ico-sm']) ?>
                        </a>
                        <?php if ($canManage): ?>
                            <form method="post" action="<?= e(url('documents/' . rawurlencode((string) $d['id']) . '/delete')) ?>"
                                  style="display:inline" onsubmit="return confirm('Delete this document?')">
                                <?= csrf_field() ?>
                                <button type="submit" title="Delete"
                                        class="icon-box sm red" style="border:0;cursor:pointer">
                                    <?= $this->partial('partials/icon', ['name' => 'trash', 'class' => 'ico ico-sm']) ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($canManage): ?>
    <div class="slide-over" id="upload-document">
        <a href="#" class="scrim" aria-label="Close"></a>
        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2 class="h3">Upload a document</h2>
                    <p class="small muted mt-8px">Receipts, certificates, contracts and photos</p>
                </div>
                <a href="#" class="panel-close" aria-label="Close"><?= $this->partial('partials/icon', ['name' => 'x', 'class' => 'ico ico-sm']) ?></a>
            </div>
            <form method="post" action="<?= e(url('documents')) ?>" enctype="multipart/form-data" style="display:contents">
                <?= csrf_field() ?>
                <div class="panel-body stack">
                    <?= $this->partial('partials/field', ['name' => 'name', 'label' => 'Document name', 'required' => true]) ?>
                    <div class="field">
                        <label for="f_type">Type</label>
                        <select class="select" id="f_type" name="type">
                            <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e($label($t)) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="f_document">File <span aria-hidden="true" style="color:var(--red)">*</span></label>
                        <input class="input" type="file" id="f_document" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required
                               <?= error_for('document') ? 'aria-invalid="true"' : '' ?>>
                        <p class="hint">PDF, JPG, PNG or WEBP, up to 8 MB.</p>
                        <?php if ($er = error_for('document')): ?><p class="err"><?= e($er) ?></p><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="f_notes">Notes (optional)</label>
                        <textarea class="textarea" id="f_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="panel-foot">
                    <a href="#" class="btn ghost block">Cancel</a>
                    <button type="submit" class="btn block">Upload</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
