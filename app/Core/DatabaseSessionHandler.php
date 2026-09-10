<?php

declare(strict_types=1);

namespace App\Core;

use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Stores session payloads in the `sessions` table instead of the shared
 * filesystem. Implements the timestamp + id interfaces so PHP's strict mode
 * and lazy-write optimisations work correctly.
 */
final class DatabaseSessionHandler implements
    SessionHandlerInterface,
    SessionIdInterface,
    SessionUpdateTimestampHandlerInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function create_sid(): string
    {
        return bin2hex(random_bytes(24)); // 48 hex chars
    }

    #[\ReturnTypeWillChange]
    public function read(string $id): string
    {
        $row = $this->db->selectOne(
            'SELECT payload FROM sessions WHERE id = :id LIMIT 1',
            ['id' => $id],
        );
        return $row === null ? '' : (string) $row['payload'];
    }

    public function write(string $id, string $data): bool
    {
        $now = time();
        $userId = isset($_SESSION['auth']['user_id']) ? (string) $_SESSION['auth']['user_id'] : null;
        $adminId = isset($_SESSION['admin']['admin_id']) ? (string) $_SESSION['admin']['admin_id'] : null;

        $this->db->run(
            'INSERT INTO sessions (id, user_id, admin_id, ip_address, user_agent, payload, last_activity)
             VALUES (:id, :user_id, :admin_id, :ip, :ua, :payload, :ts)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                admin_id = VALUES(admin_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                payload = VALUES(payload),
                last_activity = VALUES(last_activity)',
            [
                'id'       => $id,
                'user_id'  => $userId,
                'admin_id' => $adminId,
                'ip'       => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                'ua'       => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'payload'  => $data,
                'ts'       => $now,
            ],
        );
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->db->delete('sessions', ['id' => $id]);
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc(int $maxLifetime): int
    {
        $cutoff = time() - $maxLifetime;
        return $this->db->run(
            'DELETE FROM sessions WHERE last_activity < :cutoff',
            ['cutoff' => $cutoff],
        )->rowCount();
    }

    public function validateId(string $id): bool
    {
        if (preg_match('/^[a-f0-9]{48}$/', $id) !== 1) {
            return false;
        }
        $exists = $this->db->scalar('SELECT 1 FROM sessions WHERE id = :id LIMIT 1', ['id' => $id]);
        return $exists !== false;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        $this->db->update('sessions', ['last_activity' => time()], ['id' => $id]);
        return true;
    }
}
