<?php
/**
 * @var string $subject
 * @var string $appName
 * @var string $appUrl
 */
$appName = $appName ?? 'AgriVault';
?><!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;background:#F1F5F9;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1E293B">
    <div style="max-width:520px;margin:0 auto;padding:28px 16px">
        <div style="font-weight:800;font-size:18px;color:#0F172A;margin-bottom:18px"><?= e($appName) ?></div>
        <div style="background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:26px">
            <?= $this->yieldContent() ?>
        </div>
        <p style="color:#94A3B8;font-size:12px;margin-top:18px">
            You’re receiving this because someone used this address on <?= e($appName) ?>.
            If it wasn’t you, you can safely ignore this email.
        </p>
    </div>
</body>
</html>
