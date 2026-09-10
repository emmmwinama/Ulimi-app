<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Dates;

/**
 * Back-office administrator authentication. Entirely separate from customer
 * Auth: different table, different session key, different login route. A
 * compromised customer session can never reach an admin route and vice versa.
 */
final class AdminAuth
{
    private const SESSION_KEY = 'admin';

    /** @var array<string,mixed>|null */
    private static ?array $cached = null;
    private static bool $resolved = false;

    /** @return array<string,mixed>|null */
    public static function attempt(string $email, string $password): ?array
    {
        $db = Database::instance();
        $admin = $db->selectOne(
            'SELECT * FROM admin_users WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower(trim($email))],
        );

        if ($admin === null) {
            password_verify($password, '$2y$12$usesomesillystringforsalttc0000000000000000000000000000000');
            return null;
        }
        if (!password_verify($password, (string) $admin['password'])) {
            return null;
        }
        if (Auth::needsRehash((string) $admin['password'])) {
            $db->update('admin_users', ['password' => Auth::hash($password)], ['id' => $admin['id']]);
        }
        return $admin;
    }

    /** @param array<string,mixed> $admin */
    public static function login(array $admin): void
    {
        Session::instance()->regenerate(true);
        Csrf::rotate();

        $_SESSION[self::SESSION_KEY] = [
            'admin_id' => (string) $admin['id'],
            'login_at' => time(),
        ];
        self::$cached = $admin;
        self::$resolved = true;

        Database::instance()->update(
            'admin_users',
            ['last_login_at' => Dates::nowUtc()],
            ['id' => $admin['id']],
        );
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$cached = null;
        self::$resolved = true;
        Session::instance()->regenerate(true);
        Csrf::rotate();
    }

    public static function check(): bool
    {
        return self::admin() !== null;
    }

    public static function id(): ?string
    {
        $raw = $_SESSION[self::SESSION_KEY]['admin_id'] ?? null;
        return $raw === null ? null : (string) $raw;
    }

    public static function isSuper(): bool
    {
        $admin = self::admin();
        return $admin !== null && (int) $admin['is_super_admin'] === 1;
    }

    /** @return array<string,mixed>|null */
    public static function admin(): ?array
    {
        if (self::$resolved) {
            return self::$cached;
        }
        self::$resolved = true;

        $id = self::id();
        if ($id === null) {
            return self::$cached = null;
        }

        $admin = Database::instance()->selectOne(
            'SELECT * FROM admin_users WHERE id = :id LIMIT 1',
            ['id' => $id],
        );
        if ($admin === null) {
            unset($_SESSION[self::SESSION_KEY]);
            return self::$cached = null;
        }
        return self::$cached = $admin;
    }

    public static function flushCache(): void
    {
        self::$cached = null;
        self::$resolved = false;
    }
}
