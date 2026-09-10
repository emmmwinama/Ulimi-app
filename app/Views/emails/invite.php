<?php $this->layout('emails/layout'); /** @var string $farmName @var string $role @var string $link @var int $ttlHours */ ?>
<?php $this->start('content'); ?>
<h1 style="font-size:18px;margin:0 0 12px;color:#0F172A">You’ve been invited</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6">
    You’ve been invited to join <strong><?= e($farmName) ?></strong> on AgriVault as
    <strong><?= e($role) ?></strong>.
</p>
<p style="margin:0 0 20px">
    <a href="<?= e($link) ?>"
       style="display:inline-block;background:#0284C7;color:#fff;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:8px">
       View invitation
    </a>
</p>
<p style="margin:0;font-size:13px;color:#64748B;line-height:1.6">
    Or paste this link into your browser:<br>
    <span style="word-break:break-all"><?= e($link) ?></span>
</p>
<p style="margin:16px 0 0;font-size:13px;color:#64748B">
    This invitation expires in <?= e((string) ($ttlHours ?? 72)) ?> hours and must be accepted from this email address.
</p>
<?php $this->stop(); ?>
