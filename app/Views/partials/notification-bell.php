<?php
use App\Core\Auth;
use App\Repositories\NotificationRepository;

$unread = (new NotificationRepository())->unreadCount((string) Auth::id());
?>
<a href="<?= e(url('notifications')) ?>" class="icon-btn" aria-label="Notifications<?= $unread > 0 ? ', ' . $unread . ' unread' : '' ?>">
    <?= $this->partial('partials/icon', ['name' => 'bell', 'class' => 'ico']) ?>
    <?php if ($unread > 0): ?>
        <span class="dot"><?= $unread > 9 ? '9+' : e((string) $unread) ?></span>
    <?php endif; ?>
</a>
