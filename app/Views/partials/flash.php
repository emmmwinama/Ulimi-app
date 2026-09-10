<?php
/** Renders queued flash messages. Auto-dismiss handled by app.js. */
use App\Core\Flash;

$messages = Flash::pull();
if ($messages === []) {
    return;
}
?>
<div class="flash-stack" role="status" aria-live="polite">
    <?php foreach ($messages as $m): ?>
        <?php $level = in_array($m['level'], ['success', 'error', 'warning', 'info'], true) ? $m['level'] : 'info'; ?>
        <div class="alert <?= e($level) ?>" data-flash>
            <?= $this->partial('partials/icon', [
                'name' => match ($level) {
                    'success' => 'check-circle',
                    'error'   => 'alert',
                    'warning' => 'alert',
                    default   => 'info',
                },
                'class' => 'ico',
            ]) ?>
            <div><?= e($m['text']) ?></div>
        </div>
    <?php endforeach; ?>
</div>
