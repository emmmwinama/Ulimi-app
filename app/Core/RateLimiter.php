<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Database-backed fixed-window rate limiter. No APCu/Redis on the target host,
 * so state lives in the `rate_limits` table. Buckets are hashed so a raw
 * email/IP is never stored.
 */
final class RateLimiter
{
    private function __construct(private readonly Database $db)
    {
    }

    public static function instance(): self
    {
        return new self(Database::instance());
    }

    private function key(string $bucket): string
    {
        return hash('sha256', $bucket . '|' . (string) Config::get('app.key', ''));
    }

    public function tooManyAttempts(string $bucket, int $maxAttempts): bool
    {
        return $this->attempts($bucket) >= $maxAttempts;
    }

    public function attempts(string $bucket): int
    {
        $row = $this->db->selectOne(
            'SELECT attempts, expires_at FROM rate_limits WHERE bucket = :b LIMIT 1',
            ['b' => $this->key($bucket)],
        );
        if ($row === null) {
            return 0;
        }
        if ((int) $row['expires_at'] <= time()) {
            return 0;
        }
        return (int) $row['attempts'];
    }

    /** Record one attempt; returns the running count in the current window. */
    public function hit(string $bucket, int $decaySeconds): int
    {
        $key = $this->key($bucket);
        $now = time();
        $expires = $now + $decaySeconds;

        // Named placeholders are not reused: native (non-emulated) prepared
        // statements reject a name that appears more than once.
        $this->db->run(
            'INSERT INTO rate_limits (bucket, attempts, expires_at)
             VALUES (:b, 1, :exp1)
             ON DUPLICATE KEY UPDATE
                attempts   = IF(expires_at <= :now1, 1, attempts + 1),
                expires_at = IF(expires_at <= :now2, :exp2, expires_at)',
            ['b' => $key, 'exp1' => $expires, 'exp2' => $expires, 'now1' => $now, 'now2' => $now],
        );

        return $this->attempts($bucket);
    }

    /** Seconds until the current window resets (0 if not limited). */
    public function availableIn(string $bucket): int
    {
        $row = $this->db->selectOne(
            'SELECT expires_at FROM rate_limits WHERE bucket = :b LIMIT 1',
            ['b' => $this->key($bucket)],
        );
        if ($row === null) {
            return 0;
        }
        return max(0, (int) $row['expires_at'] - time());
    }

    public function clear(string $bucket): void
    {
        $this->db->delete('rate_limits', ['bucket' => $this->key($bucket)]);
    }

    /** Opportunistic cleanup of expired buckets. Cheap; called from auth paths. */
    public function purgeExpired(): void
    {
        $this->db->run('DELETE FROM rate_limits WHERE expires_at <= :now', ['now' => time()]);
    }
}
