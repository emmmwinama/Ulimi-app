<?php $this->layout('emails/layout'); /** @var string $name @var string $link @var int $ttlMinutes */ ?>
<?php $this->start('content'); ?>
<h1 style="font-size:18px;margin:0 0 12px;color:#0F172A">Reset your password</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6">
    Hi <?= e($name) ?>, we received a request to reset your AgriVault password.
    Choose a new one here:
</p>
<p style="margin:0 0 20px">
    <a href="<?= e($link) ?>"
       style="display:inline-block;background:#0284C7;color:#fff;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:8px">
       Choose a new password
    </a>
</p>
<p style="margin:0;font-size:13px;color:#64748B;line-height:1.6">
    Or paste this link into your browser:<br>
    <span style="word-break:break-all"><?= e($link) ?></span>
</p>
<p style="margin:16px 0 0;font-size:13px;color:#64748B">
    This link expires in <?= e((string) ($ttlMinutes ?? 60)) ?> minutes. If you didn’t ask for this, ignore this email — your password won’t change.
</p>
<?php $this->stop(); ?>
