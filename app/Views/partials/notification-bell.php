<?php
use App\Core\Auth;
use App\Repositories\NotificationRepository;

$unread = (new NotificationRepository())->unreadCount((string) Auth::id());
?>
<a href="<?= e(url('notifications')) ?>" aria-label="Notifications<?= $unread > 0 ? ', ' . $unread . ' unread' : '' ?>"
   style="position:relative;display:inline-grid;place-items:center;width:var(--tap);height:var(--tap);border:1px solid var(--line-strong);border-radius:999px;background:var(--surface);text-decoration:none;color:var(--navy-600)">
    <?= $this->partial('partials/icon', ['name' => 'bell', 'class' => 'ico']) ?>
    <?php if ($unread > 0): ?>
        <span style="position:absolute;top:-2px;right:-2px;min-width:16px;height:16px;padding:0 3px;border-radius:999px;background:var(--red);color:#fff;font-size:.65rem;font-weight:700;display:grid;place-items:center;line-height:1">
            <?= $unread > 9 ? '9+' : e((string) $unread) ?>
        </span>
    <?php endif; ?>
</a>
