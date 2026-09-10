<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;

/**
 * One-time credential tokens (activation, password reset, team invite).
 *
 * The plaintext token is emailed to the user; only a keyed hash is stored, so a
 * database leak does not hand an attacker working tokens. Verification is
 * constant-time.
 */
final class Token
{
    /** @return array{plain:string,hash:string} */
    public static function create(int $bytes = 32): array
    {
        $plain = rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
        return ['plain' => $plain, 'hash' => self::hash($plain)];
    }

    public static function hash(string $plain): string
    {
        return hash_hmac('sha256', $plain, (string) Config::get('app.key', ''));
    }

    public static function matches(string $plain, string $storedHash): bool
    {
        return hash_equals($storedHash, self::hash($plain));
    }
}
