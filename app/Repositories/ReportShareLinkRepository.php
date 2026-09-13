<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Token;
use App\Support\Ulid;

/**
 * Consent-based, token-gated read access to one record pack. Only the keyed
 * hash of the token is stored (same pattern as AuthTokenRepository) — unlike
 * activation/reset tokens this is repeatable-use (a lender may reopen the
 * link several times) until it expires or the farmer revokes it.
 */
final class ReportShareLinkRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** Returns the PLAINTEXT token — shown to the farmer once, never stored. */
    public function create(string $farmId, string $userId, string $packType, int $ttlDays): string
    {
        $token = Token::create();
        $this->db->insert('report_share_links', [
            'id'             => Ulid::generate(),
            'farm_id'        => $farmId,
            'pack_type'      => $packType,
            'token_hash'     => $token['hash'],
            'created_by_id'  => $userId,
            'expires_at'     => date(Dates::DB_FORMAT, time() + $ttlDays * 86400),
            'revoked_at'     => null,
            'last_viewed_at' => null,
            'view_count'     => 0,
            'created_at'     => Dates::nowUtc(),
        ]);
        return $token['plain'];
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId): array
    {
        return $this->db->select(
            'SELECT * FROM report_share_links WHERE farm_id = :fid ORDER BY created_at DESC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM report_share_links WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function revoke(string $farmId, string $id): void
    {
        $this->db->update('report_share_links', ['revoked_at' => Dates::nowUtc()], ['id' => $id, 'farm_id' => $farmId]);
    }

    /**
     * Resolve a plaintext token to its still-valid share link, or null if it
     * doesn't exist, is expired, or was revoked. Does not distinguish those
     * cases in its return value — callers should show one generic message,
     * the same enumeration-safety principle as login/reset token handling.
     *
     * @return array<string,mixed>|null
     */
    public function resolveActive(string $plainToken): ?array
    {
        $row = $this->db->selectOne(
            'SELECT * FROM report_share_links WHERE token_hash = :hash LIMIT 1',
            ['hash' => Token::hash($plainToken)],
        );
        if ($row === null || $row['revoked_at'] !== null) {
            return null;
        }
        if (strtotime((string) $row['expires_at'] . ' UTC') < time()) {
            return null;
        }
        if (!Token::matches($plainToken, (string) $row['token_hash'])) {
            return null;
        }
        return $row;
    }

    public function recordView(string $id): void
    {
        $this->db->run(
            'UPDATE report_share_links SET view_count = view_count + 1, last_viewed_at = :now WHERE id = :id',
            ['now' => Dates::nowUtc(), 'id' => $id],
        );
    }
}
