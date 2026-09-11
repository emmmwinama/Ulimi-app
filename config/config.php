<?php

declare(strict_types=1);

/**
 * Base configuration. Safe, non-secret defaults live here and are committed.
 * Real per-environment values come from config/config.local.php (git-ignored),
 * which is deep-merged over this array by App\Core\Config.
 */

return [
    'app' => [
        'name'  => 'AgriVault',
        'env'   => 'production',
        'debug' => false,
        'url'   => 'http://localhost',
        'key'   => '',              // REQUIRED via config.local.php
        'timezone' => 'Africa/Blantyre',
        'locale'   => 'en',
        'currency' => 'MWK',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'agrivault',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name'            => 'agv_sess',
        'lifetime'        => 60 * 60 * 8,   // absolute max age (seconds)
        'idle_timeout'    => 60 * 60 * 2,   // re-auth after this much inactivity
        'regenerate_every'=> 60 * 15,       // rotate id at least this often
        'cookie_path'     => '/',
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => true,          // downgraded automatically on plain-HTTP local
    ],

    'security' => [
        // password_hash algorithm: PASSWORD_ARGON2ID if the build supports it,
        // resolved at runtime in App\Core\Auth. bcrypt cost used as fallback.
        'bcrypt_cost'          => 12,
        'token_ttl'            => 60 * 60,       // activation / reset token lifetime
        'invite_ttl'           => 60 * 60 * 72,  // team invite lifetime
        'login_max_attempts'   => 5,
        'login_decay_seconds'  => 15 * 60,
        'form_max_attempts'    => 8,             // contact / demo / register
        'form_decay_seconds'   => 60 * 60,
        // Content-Security-Policy. Scripts/styles/fonts are all self-hosted.
        // The only external origins are raster map tiles (OpenStreetMap and
        // Esri World Imagery) loaded as <img> by Leaflet — images only, no
        // scripts, no data exfiltration path.
        'csp' => "default-src 'self'; "
               . "img-src 'self' data: https://*.tile.openstreetmap.org https://server.arcgisonline.com https://*.basemaps.cartocdn.com; "
               . "style-src 'self' 'unsafe-inline'; script-src 'self'; "
               . "connect-src 'self'; font-src 'self'; "
               . "object-src 'none'; base-uri 'self'; form-action 'self'; "
               . "frame-ancestors 'none'",
    ],

    'mail' => [
        'driver'     => 'log',
        'host'       => '',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => '',
        'password'   => '',
        'from_email' => 'no-reply@agrivault.local',
        'from_name'  => 'AgriVault',
    ],

    'uploads' => [
        'max_bytes'     => 8 * 1024 * 1024,  // 8 MB per document
        'allowed_mime'  => [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            // Voice-note formats for incident/health-record attachments (Phase 14).
            'audio/mpeg'      => 'mp3',
            'audio/mp4'       => 'm4a',
            'audio/x-m4a'     => 'm4a',
            'audio/ogg'       => 'ogg',
        ],
        'path' => null,  // resolved to storage/uploads in bootstrap
    ],

    'ai' => [
        // Groq's free tier serves open-weight models (Llama 3.3, etc.) over an
        // OpenAI-compatible endpoint at no cost, with generous rate limits —
        // no local model runtime needed, which a free shared host couldn't run
        // anyway. Feature no-ops (hidden, not broken) when api_key is empty.
        'api_key'  => '',  // REQUIRED via config.local.php to enable AI features
        'base_url' => 'https://api.groq.com/openai/v1',
        'model'    => 'llama-3.3-70b-versatile',
    ],

    'trusted_proxies' => [],

    'paths' => [], // filled in by bootstrap.php
];
