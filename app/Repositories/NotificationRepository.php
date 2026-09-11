<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class NotificationRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forUser(string $userId, int $limit = 30): array
    {
        return $this->db->select(
            'SELECT * FROM notifications WHERE user_id = :uid ORDER BY is_read ASC, created_at DESC LIMIT ' . max(1, $limit),
            ['uid' => $userId],
        );
    }

    public function unreadCount(string $userId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0',
            ['uid' => $userId],
        );
    }

    /**
     * Insert if the (user, dedupe_key) pair doesn't already exist. Silent no-op
     * on the duplicate — this is how "regenerate on every dashboard load"
     * avoids piling up repeats for the same underlying event.
     *
     * @param array{type:string,dedupe_key:string,title:string,message:string,link:?string} $data
     */
    public function upsert(string $userId, string $farmId, array $data): void
    {
        $exists = $this->db->scalar(
            'SELECT 1 FROM notifications WHERE user_id = :uid AND dedupe_key = :key LIMIT 1',
            ['uid' => $userId, 'key' => $data['dedupe_key']],
        );
        if ($exists !== false) {
            return;
        }
        $this->db->insert('notifications', [
            'id' => Ulid::generate(), 'user_id' => $userId, 'farm_id' => $farmId,
            'type' => $data['type'], 'dedupe_key' => $data['dedupe_key'],
            'title' => $data['title'], 'message' => $data['message'],
            'is_read' => 0, 'link' => $data['link'], 'created_at' => Dates::nowUtc(),
        ]);
    }

    public function markRead(string $userId, string $id): void
    {
        $this->db->update('notifications', ['is_read' => 1], ['id' => $id, 'user_id' => $userId]);
    }

    public function markAllRead(string $userId): void
    {
        $this->db->run('UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0', ['uid' => $userId]);
    }

    /** Drop dedupe keys older than N days whose underlying condition may have changed. */
    public function pruneStale(string $userId, int $days = 60): void
    {
        $this->db->run(
            'DELETE FROM notifications WHERE user_id = :uid AND is_read = 1 AND created_at < :cutoff',
            ['uid' => $userId, 'cutoff' => date(Dates::DB_FORMAT, time() - $days * 86400)],
        );
    }
}
