<?php
/**
 * @var string $tab
 * @var array<int,array<string,mixed>> $contacts @var array<int,array<string,mixed>> $demos
 */
$this->layout('layouts/admin');
use App\Support\Dates;
?>
<?php $this->start('content'); ?>
<div class="page-head"><div><h1 class="h1">Inquiries</h1></div></div>

<div class="row mb-16" style="gap:8px">
    <a class="btn sm <?= $tab === 'demo' ? 'secondary' : '' ?>" href="<?= e(url('admin/inquiries')) ?>">Contact (<?= count($contacts) ?>)</a>
    <a class="btn sm <?= $tab === 'demo' ? '' : 'secondary' ?>" href="<?= e(url('admin/inquiries?tab=demo')) ?>">Demo requests (<?= count($demos) ?>)</a>
</div>

<?php if ($tab === 'demo'): ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Name</th><th>Email</th><th>Farm</th><th>Message</th><th>Status</th><th>Received</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php if ($demos === []): ?><tr><td colspan="7" class="muted">No demo requests.</td></tr>
        <?php else: foreach ($demos as $d): ?>
            <tr>
                <td><?= e((string) $d['name']) ?></td>
                <td class="small"><?= e((string) $d['email']) ?></td>
                <td class="small"><?= e((string) $d['farm']) ?></td>
                <td class="small muted"><?= e((string) ($d['message'] ?: '—')) ?></td>
                <td><span class="badge <?= $d['status'] === 'pending' ? 'amber' : 'green' ?>"><?= e(ucfirst((string) $d['status'])) ?></span></td>
                <td class="small muted"><?= e(Dates::forDisplay((string) $d['created_at'])) ?></td>
                <td class="num nowrap">
                    <?php foreach (['confirmed', 'completed', 'cancelled'] as $st): ?>
                        <form method="post" action="<?= e(url('admin/inquiries/demo/' . rawurlencode((string) $d['id']) . '/status')) ?>" style="display:inline">
                            <?= csrf_field() ?><input type="hidden" name="status" value="<?= e($st) ?>">
                            <button class="btn sm ghost" type="submit"><?= e(ucfirst($st)) ?></button>
                        </form>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div>
<?php else: ?>
    <div class="table-wrap"><table class="data">
        <thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Status</th><th>Received</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php if ($contacts === []): ?><tr><td colspan="6" class="muted">No contact submissions.</td></tr>
        <?php else: foreach ($contacts as $c): ?>
            <tr>
                <td><?= e((string) $c['name']) ?></td>
                <td class="small"><?= e((string) $c['email']) ?></td>
                <td class="small muted"><?= e((string) $c['message']) ?></td>
                <td><span class="badge <?= $c['status'] === 'new' ? 'amber' : 'green' ?>"><?= e(ucfirst((string) $c['status'])) ?></span></td>
                <td class="small muted"><?= e(Dates::forDisplay((string) $c['created_at'])) ?></td>
                <td class="num nowrap">
                    <?php foreach (['read', 'replied', 'archived'] as $st): ?>
                        <form method="post" action="<?= e(url('admin/inquiries/contact/' . rawurlencode((string) $c['id']) . '/status')) ?>" style="display:inline">
                            <?= csrf_field() ?><input type="hidden" name="status" value="<?= e($st) ?>">
                            <button class="btn sm ghost" type="submit"><?= e(ucfirst($st)) ?></button>
                        </form>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div>
<?php endif; ?>
<?php $this->stop(); ?>
