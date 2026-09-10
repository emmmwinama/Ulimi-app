<?php

declare(strict_types=1);

namespace App\Support;

final class Str
{
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', $separator, $value) ?? '';
        return trim($value, $separator);
    }

    public static function initials(string $name, int $max = 2): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            if (mb_strlen($letters) >= $max) {
                break;
            }
        }
        return $letters === '' ? '?' : $letters;
    }

    public static function limit(string $value, int $limit = 100, string $end = '…'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }

    public static function mask(string $value, int $visible = 2): string
    {
        $len = mb_strlen($value);
        if ($len <= $visible) {
            return str_repeat('•', $len);
        }
        return mb_substr($value, 0, $visible) . str_repeat('•', max(3, $len - $visible));
    }

    /** Redact the local-part of an email for display: e***@example.com */
    public static function maskEmail(string $email): string
    {
        $at = strpos($email, '@');
        if ($at === false || $at < 1) {
            return '•••';
        }
        $local = substr($email, 0, $at);
        $domain = substr($email, $at);
        return mb_substr($local, 0, 1) . str_repeat('*', max(2, mb_strlen($local) - 1)) . $domain;
    }
}
