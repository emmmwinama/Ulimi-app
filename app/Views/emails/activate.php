<?php $this->layout('emails/layout'); /** @var string $name @var string $link */ ?>
<?php $this->start('content'); ?>
<h1 style="font-size:18px;margin:0 0 12px;color:#0F172A">Activate your account</h1>
<p style="margin:0 0 16px;font-size:15px;line-height:1.6">
    Hi <?= e($name) ?>, thanks for signing up. Confirm your email address to activate
    your AgriVault account:
</p>
<p style="margin:0 0 20px">
    <a href="<?= e($link) ?>"
       style="display:inline-block;background:#0284C7;color:#fff;text-decoration:none;font-weight:700;padding:12px 20px;border-radius:8px">
       Activate my account
    </a>
</p>
<p style="margin:0;font-size:13px;color:#64748B;line-height:1.6">
    Or paste this link into your browser:<br>
    <span style="word-break:break-all"><?= e($link) ?></span>
</p>
<p style="margin:16px 0 0;font-size:13px;color:#64748B">This link expires in 24 hours.</p>
<?php $this->stop(); ?>
