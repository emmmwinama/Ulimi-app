<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request-scoped identity for the mobile JWT API. Unlike Auth (session-based),
 * this is populated once per request by Middleware\AuthenticateApi from a
 * verified JWT — there is no server-side session for API clients.
 */
final class ApiAuth
{
    /** @var array<string,mixed>|null */
    private static ?array $user = null;

    /** @param array<string,mixed> $user */
    public static function set(array $user): void
    {
        self::$user = $user;
    }

    public static function clear(): void
    {
        self::$user = null;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        return self::$user;
    }

    public static function id(): ?string
    {
        return self::$user !== null ? (string) self::$user['id'] : null;
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }
}
