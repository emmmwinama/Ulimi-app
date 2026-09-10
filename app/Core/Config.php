<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable-ish config store. Populated once in bootstrap; read anywhere via
 * dot-notation keys (`Config::get('db.host')`).
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    /** @param array<string,mixed> $items */
    public static function load(array $items): void
    {
        self::$items = $items;
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        return self::$items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__missing__') !== '__missing__';
    }

    /**
     * Recursively merge $override into $base. Numeric-keyed arrays are replaced
     * wholesale (so a local config can override a whitelist rather than append).
     *
     * @param array<mixed> $base
     * @param array<mixed> $override
     * @return array<mixed>
     */
    public static function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                is_string($key)
                && is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && self::isAssoc($base[$key])
            ) {
                $base[$key] = self::deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /** @param array<mixed> $array */
    private static function isAssoc(array $array): bool
    {
        if ($array === []) {
            return true;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
