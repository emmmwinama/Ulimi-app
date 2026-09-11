<?php
/** @var array<int,array<string,mixed>> $items */
$this->layout('layouts/app');
use App\Support\Dates;
$unread = count(array_filter($items, static fn ($n) => (int) $n['is_read'] === 0));
?>
<?php $this->start('content'); ?>
<div class="page-head">
    <div>
        <h1 class="h1">Notifications</h1>
        <p class="lede"><?= $unread ?> unread</p>
    </div>
    <?php if ($unread > 0): ?>
        <form method="post" action="<?= e(url('notifications/read-all')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn secondary">Mark all read</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($items === []): ?>
    <div class="empty"><div class="h3">Nothing here</div><p>Harvest reminders and activity alerts will show up here.</p></div>
<?php else: ?>
    <div class="stack" style="--stack-gap:0">
        <?php foreach ($items as $n): ?>
            <form method="post" action="<?= e(url('notifications/' . rawurlencode((string) $n['id']) . '/read')) ?>"
                  class="card mb-8" style="<?= (int) $n['is_read'] === 0 ? 'border-left:3px solid var(--blue)' : 'opacity:.7' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="link" value="<?= e((string) ($n['link'] ?? '')) ?>">
                <button type="submit" class="card-body" style="width:100%;text-align:left;background:none;border:0;cursor:pointer;display:block">
                    <div class="spread">
                        <strong><?= e((string) $n['title']) ?></strong>
                        <span class="small muted"><?= e(Dates::forDisplay((string) $n['created_at'])) ?></span>
                    </div>
                    <p class="small muted mt-8" style="margin-bottom:0"><?= e((string) $n['message']) ?></p>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $this->stop(); ?>
