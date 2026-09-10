<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;

/**
 * Money is stored as DECIMAL(14,2) in the database and handled here as an
 * integer number of minor units (e.g. tambala for MWK) to avoid float drift in
 * arithmetic. Parse at the edge, format at the edge, keep integers in between.
 */
final class Money
{
    public static function toMinor(int|float|string $amount): int
    {
        if (is_string($amount)) {
            $amount = (float) preg_replace('/[^0-9.\-]/', '', $amount);
        }
        return (int) round($amount * 100);
    }

    public static function toDecimalString(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    public static function format(int|float|string $amount, ?string $currency = null): string
    {
        $currency ??= (string) Config::get('app.currency', 'MWK');
        $minor = is_int($amount) ? $amount : self::toMinor($amount);
        $major = $minor / 100;
        return $currency . ' ' . number_format($major, 2);
    }

    /** Compact display for dashboards: MWK 7.8M, MWK 350K. */
    public static function compact(int|float|string $amount, ?string $currency = null): string
    {
        $currency ??= (string) Config::get('app.currency', 'MWK');
        $value = is_int($amount) ? $amount / 100 : (float) $amount;
        $abs = abs($value);

        [$divisor, $suffix] = match (true) {
            $abs >= 1_000_000_000 => [1_000_000_000, 'B'],
            $abs >= 1_000_000     => [1_000_000, 'M'],
            $abs >= 1_000         => [1_000, 'K'],
            default               => [1, ''],
        };

        $scaled = $value / $divisor;
        $formatted = $suffix === '' ? number_format($scaled, 0) : rtrim(rtrim(number_format($scaled, 1), '0'), '.');
        return $currency . ' ' . $formatted . $suffix;
    }
}
