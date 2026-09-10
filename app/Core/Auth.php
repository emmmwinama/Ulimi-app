<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Dates;

/**
 * Customer (farm-user) authentication. Admin auth is a separate domain
 * (App\Core\AdminAuth) with its own session namespace.
 *
 * Password storage: Argon2id when the PHP build provides it, otherwise bcrypt
 * at the configured cost. Hashes are transparently upgraded on next login.
 */
final class Auth
{
    private const SESSION_KEY = 'auth';

    /** @var array<string,mixed>|null per-request cache of the current user row */
    private static ?array $cachedUser = null;
    private static bool $resolved = false;

    public static function hash(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 64 * 1024,
                'time_cost'   => 4,
                'threads'     => 2,
            ]);
        }
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => (int) Config::get('security.bcrypt_cost', 12),
        ]);
    }

    public static function needsRehash(string $hash): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $options = $algo === PASSWORD_BCRYPT
            ? ['cost' => (int) Config::get('security.bcrypt_cost', 12)]
            : ['memory_cost' => 64 * 1024, 'time_cost' => 4, 'threads' => 2];
        return password_needs_rehash($hash, $algo, $options);
    }

    /**
     * Verify credentials. Returns the user row on success, null otherwise.
     * Runs a dummy hash on unknown email so response timing does not reveal
     * whether an account exists.
     *
     * @return array<string,mixed>|null
     */
    public static function attempt(string $email, string $password): ?array
    {
        $db = Database::instance();
        $user = $db->selectOne(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower(trim($email))],
        );

        if ($user === null) {
            // Constant-work path.
            password_verify($password, '$2y$12$usesomesillystringforsalttc0000000000000000000000000000000');
            return null;
        }

        if (!password_verify($password, (string) $user['password'])) {
            return null;
        }

        if (self::needsRehash((string) $user['password'])) {
            $db->update('users', ['password' => self::hash($password)], ['id' => $user['id']]);
        }

        return $user;
    }

    /** @param array<string,mixed> $user */
    public static function login(array $user, bool $remember = false): void
    {
        $session = Session::instance();
        $session->regenerate(true);          // defeat session fixation
        Csrf::rotate();                       // new anti-CSRF secret post-auth

        $_SESSION[self::SESSION_KEY] = [
            'user_id'  => (string) $user['id'],
            'login_at' => time(),
        ];

        self::$cachedUser = $user;
        self::$resolved = true;

        Database::instance()->update(
            'users',
            ['last_login_at' => Dates::nowUtc()],
            ['id' => $user['id']],
        );
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$cachedUser = null;
        self::$resolved = true;
        Session::instance()->regenerate(true);
        Csrf::rotate();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?string
    {
        $raw = $_SESSION[self::SESSION_KEY]['user_id'] ?? null;
        return $raw === null ? null : (string) $raw;
    }

    /**
     * The current user, reloaded from the database once per request so a
     * deactivated or deleted account is rejected immediately.
     *
     * @return array<string,mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$cachedUser;
        }
        self::$resolved = true;

        $id = self::id();
        if ($id === null) {
            return self::$cachedUser = null;
        }

        $user = Database::instance()->selectOne(
            'SELECT * FROM users WHERE id = :id LIMIT 1',
            ['id' => $id],
        );

        if ($user === null || (int) $user['is_active'] !== 1) {
            unset($_SESSION[self::SESSION_KEY]);
            return self::$cachedUser = null;
        }

        return self::$cachedUser = $user;
    }

    /** Test seam. */
    public static function flushCache(): void
    {
        self::$cachedUser = null;
        self::$resolved = false;
    }
}
