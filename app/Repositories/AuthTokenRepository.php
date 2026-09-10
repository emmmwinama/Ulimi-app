<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Token;
use App\Support\Ulid;

/**
 * Single-use credential tokens for account activation and password reset.
 * Only the keyed hash of the token is stored.
 */
final class AuthTokenRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * Issue a token of the given type for a user, invalidating any prior
     * unused tokens of that type. Returns the PLAINTEXT token to email.
     */
    public function issue(string $userId, string $type, int $ttlSeconds): string
    {
        $this->db->run(
            "UPDATE auth_tokens SET used_at = :now
             WHERE user_id = :uid AND type = :type AND used_at IS NULL",
            ['now' => Dates::nowUtc(), 'uid' => $userId, 'type' => $type],
        );

        $token = Token::create();
        $this->db->insert('auth_tokens', [
            'id'         => Ulid::generate(),
            'user_id'    => $userId,
            'type'       => $type,
            'token_hash' => $token['hash'],
            'expires_at' => date(Dates::DB_FORMAT, time() + $ttlSeconds),
            'used_at'    => null,
            'created_at' => Dates::nowUtc(),
        ]);

        return $token['plain'];
    }

    /**
     * Resolve a plaintext token to its user id if it is valid, unexpired and
     * unused. Constant-time hash comparison happens in Token::matches, but we
     * still must look the row up by hash — that is safe (the stored value is a
     * keyed hash, not the secret).
     */
    public function resolve(string $plain, string $type): ?string
    {
        $row = $this->db->selectOne(
            "SELECT * FROM auth_tokens
             WHERE token_hash = :hash AND type = :type
             LIMIT 1",
            ['hash' => Token::hash($plain), 'type' => $type],
        );

        if ($row === null || $row['used_at'] !== null) {
            return null;
        }
        if (strtotime((string) $row['expires_at'] . ' UTC') < time()) {
            return null;
        }
        if (!Token::matches($plain, (string) $row['token_hash'])) {
            return null;
        }

        return (string) $row['user_id'];
    }

    public function consume(string $plain, string $type): void
    {
        $this->db->run(
            "UPDATE auth_tokens SET used_at = :now
             WHERE token_hash = :hash AND type = :type AND used_at IS NULL",
            ['now' => Dates::nowUtc(), 'hash' => Token::hash($plain), 'type' => $type],
        );
    }

    public function purgeExpired(): void
    {
        $this->db->run(
            'DELETE FROM auth_tokens WHERE expires_at < :cutoff',
            ['cutoff' => date(Dates::DB_FORMAT, time() - 86400)],
        );
    }
}
