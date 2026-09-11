<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Token;
use App\Support\Ulid;

/**
 * Refresh tokens for the mobile API. Only a keyed hash is stored — see
 * Support\Token — so a database leak never yields a usable refresh token.
 */
final class ApiTokenRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** Issue a new refresh token for a user. Returns the PLAINTEXT to send once. */
    public function issue(string $userId, int $ttlSeconds, string $deviceLabel = ''): string
    {
        $token = Token::create();
        $this->db->insert('api_refresh_tokens', [
            'id' => Ulid::generate(), 'user_id' => $userId, 'token_hash' => $token['hash'],
            'device_label' => $deviceLabel !== '' ? mb_substr($deviceLabel, 0, 120) : null,
            'expires_at' => date(Dates::DB_FORMAT, time() + $ttlSeconds),
            'revoked_at' => null, 'created_at' => Dates::nowUtc(),
        ]);
        return $token['plain'];
    }

    /** Resolve a plaintext refresh token to its user id, if valid/unrevoked/unexpired. */
    public function resolve(string $plain): ?string
    {
        $row = $this->db->selectOne(
            'SELECT * FROM api_refresh_tokens WHERE token_hash = :h LIMIT 1',
            ['h' => Token::hash($plain)],
        );
        if ($row === null || $row['revoked_at'] !== null) {
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

    public function revoke(string $plain): void
    {
        $this->db->run(
            'UPDATE api_refresh_tokens SET revoked_at = :now WHERE token_hash = :h',
            ['now' => Dates::nowUtc(), 'h' => Token::hash($plain)],
        );
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->db->run(
            'UPDATE api_refresh_tokens SET revoked_at = :now WHERE user_id = :uid AND revoked_at IS NULL',
            ['now' => Dates::nowUtc(), 'uid' => $userId],
        );
    }
}
