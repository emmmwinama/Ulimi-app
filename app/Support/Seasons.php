<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Season string helpers for the Malawi cropping calendar. Seasons are stored as
 * free text (the original does the same); this just supplies a sensible default
 * and a few suggestions for the form.
 *
 * Rain season spans roughly October–May and is labelled by the two calendar
 * years it straddles, e.g. "2025/26 Rain Season".
 */
final class Seasons
{
    public static function current(?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $year = (int) date('Y', $ts);
        $month = (int) date('n', $ts);

        // From June onward we are already preparing for the next rain season.
        $startYear = $month >= 6 ? $year : $year - 1;
        $endYear = ($startYear + 1) % 100;

        return sprintf('%d/%02d Rain Season', $startYear, $endYear);
    }

    /** @return list<string> */
    public static function suggestions(?int $timestamp = null): array
    {
        $ts = $timestamp ?? time();
        $year = (int) date('Y', $ts);
        $month = (int) date('n', $ts);
        $startYear = $month >= 6 ? $year : $year - 1;

        $out = [];
        for ($i = 1; $i >= -1; $i--) {
            $s = $startYear + $i;
            $out[] = sprintf('%d/%02d Rain Season', $s, ($s + 1) % 100);
        }
        $out[] = sprintf('%d Winter Season', $startYear + 1);
        return $out;
    }
}
