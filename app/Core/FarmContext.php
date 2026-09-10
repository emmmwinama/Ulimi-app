<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request-scoped holder for "which farm is the user acting on, and with what
 * rights". Populated by App\Middleware\ResolveFarmContext and read by
 * controllers / repositories. Repositories MUST take the farm id from here,
 * never from request input.
 */
final class FarmContext
{
    private static ?self $current = null;

    /**
     * @param array<string,mixed> $farm         row from `farms`
     * @param array<string,mixed> $member       row from `farm_members`
     * @param array<string,mixed>|null $subscription row from `subscriptions` (+ tier fields), or null
     */
    private function __construct(
        public readonly array $farm,
        public readonly array $member,
        public readonly Authz $authz,
        public readonly ?array $subscription,
    ) {
    }

    /**
     * @param array<string,mixed> $farm
     * @param array<string,mixed> $member
     * @param array<string,mixed>|null $subscription
     */
    public static function set(array $farm, array $member, Authz $authz, ?array $subscription): self
    {
        return self::$current = new self($farm, $member, $authz, $subscription);
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
            throw new \RuntimeException('No farm context resolved for this request.');
        }
        return self::$current;
    }

    public function farmId(): string
    {
        return (string) $this->farm['id'];
    }

    public function farmName(): string
    {
        return (string) $this->farm['name'];
    }

    public function can(string $ability): bool
    {
        return $this->authz->can($ability);
    }

    /** Feature flag from the active subscription tier (defaults to false). */
    public function feature(string $flag): bool
    {
        return (bool) ($this->subscription[$flag] ?? false);
    }

    /** Numeric resource limit from the tier; -1 means unlimited, null means unknown. */
    public function limit(string $key): ?int
    {
        if ($this->subscription === null || !array_key_exists($key, $this->subscription)) {
            return null;
        }
        return (int) $this->subscription[$key];
    }

    public function subscriptionStatus(): string
    {
        return (string) ($this->subscription['status'] ?? 'none');
    }

    /** True when writes should be blocked (expired / suspended subscription). */
    public function isReadOnly(): bool
    {
        return in_array($this->subscriptionStatus(), ['expired', 'suspended'], true);
    }
}
