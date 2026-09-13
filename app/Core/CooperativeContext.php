<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request-scoped holder for "which cooperative is the active farm acting
 * on, and with what role" — a second tenant boundary alongside FarmContext,
 * same trust model: membership is re-verified against the database on
 * every request by Middleware\ResolveCooperativeContext, never trusted
 * from a client-supplied cooperative id.
 */
final class CooperativeContext
{
    private static ?self $current = null;

    private const MANAGE_ROLES = ['chair', 'secretary', 'treasurer'];

    /**
     * @param array<string,mixed> $cooperative row from `cooperatives`
     * @param array<string,mixed> $member      row from `cooperative_members` for the active farm
     */
    private function __construct(
        public readonly array $cooperative,
        public readonly array $member,
    ) {
    }

    /**
     * @param array<string,mixed> $cooperative
     * @param array<string,mixed> $member
     */
    public static function set(array $cooperative, array $member): self
    {
        return self::$current = new self($cooperative, $member);
    }

    public static function clear(): void
    {
        self::$current = null;
    }

    public static function has(): bool
    {
        return self::$current !== null;
    }

    public static function current(): self
    {
        if (self::$current === null) {
            throw new \RuntimeException('No cooperative context resolved for this request.');
        }
        return self::$current;
    }

    public function id(): string
    {
        return (string) $this->cooperative['id'];
    }

    public function name(): string
    {
        return (string) $this->cooperative['name'];
    }

    public function role(): string
    {
        return (string) $this->member['role'];
    }

    /** Chair, secretary and treasurer can manage the cooperative; a plain member cannot. */
    public function canManage(): bool
    {
        return in_array($this->role(), self::MANAGE_ROLES, true);
    }
}
