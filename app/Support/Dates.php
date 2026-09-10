<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Date helpers. Storage is always UTC `DATETIME` strings; display uses the app
 * timezone (config app.timezone).
 */
final class Dates
{
    public const DB_FORMAT = 'Y-m-d H:i:s';

    public static function nowUtc(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(self::DB_FORMAT);
    }

    /**
     * Parse an arbitrary incoming date string (ISO-8601 with offset, `Z`,
     * date-only, varying fractional precision) into a UTC DB string.
     * Returns null when unparseable.
     */
    public static function toUtc(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($value);
        } catch (\Exception) {
            $ts = strtotime($value);
            if ($ts === false) {
                return null;
            }
            $dt = (new DateTimeImmutable())->setTimestamp($ts);
        }
        return $dt->setTimezone(new DateTimeZone('UTC'))->format(self::DB_FORMAT);
    }

    public static function forDisplay(?string $dbValue, string $format = 'j M Y'): string
    {
        if ($dbValue === null || $dbValue === '') {
            return '—';
        }
        try {
            $tz = new DateTimeZone((string) \App\Core\Config::get('app.timezone', 'UTC'));
            return (new DateTimeImmutable($dbValue, new DateTimeZone('UTC')))
                ->setTimezone($tz)
                ->format($format);
        } catch (\Exception) {
            return '—';
        }
    }

    public static function isPast(?string $dbValue): bool
    {
        if ($dbValue === null || $dbValue === '') {
            return false;
        }
        return strtotime($dbValue . ' UTC') < time();
    }
}
