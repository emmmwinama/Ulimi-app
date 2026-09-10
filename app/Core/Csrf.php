<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token issue + verify. One token per session (rotated on login via
 * Session::regenerate clearing _csrf indirectly is NOT automatic, so we rotate
 * it explicitly in Auth). Verification is constant-time.
 */
final class Csrf
{
    public static function token(): string
    {
        return Session::instance()->csrfToken();
    }

    public static function rotate(): void
    {
        unset($_SESSION['_csrf']);
        Session::instance()->csrfToken();
    }

    public static function check(?string $candidate): bool
    {
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }
        $known = Session::instance()->csrfToken();
        return hash_equals($known, $candidate);
    }

    /** Pull the token from a form field or the X-CSRF-Token header. */
    public static function fromRequest(Request $request): ?string
    {
        $field = $request->input('_token');
        if (is_string($field) && $field !== '') {
            return $field;
        }
        return $request->header('X-CSRF-Token');
    }
}
