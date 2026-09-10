<?php
/**
 * Rendered directly by ErrorHandler (no View instance, no layout helpers).
 * Keep it dependency-free and self-contained.
 */
if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Something went wrong — AgriVault</title>
    <style>
        body { margin:0; font:15px/1.6 "Segoe UI",Roboto,-apple-system,sans-serif; background:#F8FAFC; color:#1E293B; }
        .box { max-width:520px; margin:14vh auto 0; padding:0 24px; text-align:center; }
        .eyebrow { font-size:.72rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#64748B; }
        h1 { font-size:1.6rem; color:#0F172A; margin:.4rem 0; }
        p { color:#64748B; }
        a { display:inline-block; margin-top:1.6rem; padding:12px 20px; border-radius:8px; background:#0284C7; color:#fff; text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
    <div class="box">
        <p class="eyebrow">Error 500</p>
        <h1>Something went wrong on our end</h1>
        <p>We’ve logged the problem. Please try again in a moment.</p>
        <a href="/">Return home</a>
    </div>
</body>
</html>
