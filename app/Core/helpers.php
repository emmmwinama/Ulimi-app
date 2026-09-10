<?php

declare(strict_types=1);

/**
 * Global helper functions. Kept deliberately small; anything with real logic
 * belongs in a class. Loaded by app/bootstrap.php before the autoloader is
 * first used in anger.
 */

use App\Core\Config;
use App\Core\Session;
use App\Core\View;

if (!function_exists('e')) {
    /**
     * HTML-escape for output in an HTML body/attribute context.
     * This is the default for every value printed in a template.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $append = ''): string
    {
        $root = (string) Config::get('paths.root', dirname(__DIR__, 2));
        return $append === '' ? $root : $root . '/' . ltrim($append, '/');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $append = ''): string
    {
        $dir = (string) Config::get('paths.storage', base_path('storage'));
        return $append === '' ? $dir : $dir . '/' . ltrim($append, '/');
    }
}

if (!function_exists('url')) {
    /** Absolute URL for an app path, based on app.url. */
    function url(string $path = ''): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** Cache-busted URL for a file under public/assets. */
    function asset(string $path): string
    {
        $rel  = 'assets/' . ltrim($path, '/');
        $full = (string) Config::get('paths.public', base_path('public')) . '/' . $rel;
        $ver  = is_file($full) ? substr((string) filemtime($full), -6) : '0';
        return url($rel) . '?v=' . $ver;
    }
}

if (!function_exists('session')) {
    function session(): Session
    {
        return Session::instance();
    }
}

if (!function_exists('old')) {
    /** Previously-submitted input, flashed on a failed validation round-trip. */
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::instance()->oldInput()[$key] ?? $default;
    }
}

if (!function_exists('error_for')) {
    /** First validation error for a field this request, or null. */
    function error_for(string $field): ?string
    {
        $bag = Session::instance()->getFlash('errors', []);
        if (is_array($bag) && isset($bag[$field][0])) {
            return (string) $bag[$field][0];
        }
        return null;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::instance()->csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        return View::instance()->render($template, $data);
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}
