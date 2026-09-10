<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Dates;
use App\Support\Ulid;
use Throwable;

/**
 * Append-only audit trail for security- and money-relevant events
 * (logins, role changes, invites, subscription/payment changes, admin actions).
 * Never throws into the caller — an audit write must not break a request.
 */
final class AuditLog
{
    /** @param array<string,mixed> $meta */
    public static function record(
        string $action,
        string $actorType = 'system',
        ?string $actorId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $farmId = null,
        array $meta = [],
        ?string $ip = null,
    ): void {
        try {
            Database::instance()->insert('audit_log', [
                'id'          => Ulid::generate(),
                'actor_type'  => in_array($actorType, ['user', 'admin', 'system'], true) ? $actorType : 'system',
                'actor_id'    => $actorId,
                'action'      => substr($action, 0, 80),
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'farm_id'     => $farmId,
                'ip'          => $ip,
                'meta'        => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_SLASHES),
                'created_at'  => Dates::nowUtc(),
            ]);
        } catch (Throwable $e) {
            Logger::instance()->warning('Audit write failed for {action}: {msg}', [
                'action' => $action,
                'msg'    => $e->getMessage(),
            ]);
        }
    }

    /** Convenience for a logged-in customer action. */
    public static function user(string $action, string $userId, array $meta = [], ?string $farmId = null, ?string $ip = null): void
    {
        self::record($action, 'user', $userId, null, null, $farmId, $meta, $ip);
    }

    /** Convenience for an admin action. */
    public static function admin(string $action, string $adminId, ?string $targetType = null, ?string $targetId = null, array $meta = [], ?string $ip = null): void
    {
        self::record($action, 'admin', $adminId, $targetType, $targetId, null, $meta, $ip);
    }
}
