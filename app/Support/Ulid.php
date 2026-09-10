<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ULID generator — 26-char Crockford base32, 48-bit millisecond timestamp
 * prefix + 80 bits of randomness. Lexicographically sortable, not guessable,
 * good B-tree locality. Used for all NEW primary keys; IDs imported from the
 * legacy SQLite (UUID/cuid/cuid2) are preserved as-is.
 */
final class Ulid
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private static string $lastTime = '';
    /** @var list<int> */
    private static array $lastRand = [];

    public static function generate(?int $timestampMs = null): string
    {
        $timestampMs ??= (int) (microtime(true) * 1000);
        $time = self::encodeTime($timestampMs);

        // Monotonic within the same millisecond: increment the previous random.
        if ($time === self::$lastTime && self::$lastRand !== []) {
            self::$lastRand = self::incrementRandom(self::$lastRand);
        } else {
            self::$lastRand = self::randomBytes16();
        }
        self::$lastTime = $time;

        return $time . self::encodeRandom(self::$lastRand);
    }

    public static function isValid(string $value): bool
    {
        return strlen($value) === 26
            && strspn(strtoupper($value), self::ALPHABET) === 26;
    }

    /** Milliseconds since epoch encoded in this ULID (for auditing). */
    public static function timestamp(string $ulid): int
    {
        $chars = str_split(strtoupper(substr($ulid, 0, 10)));
        $ms = 0;
        foreach ($chars as $char) {
            $ms = $ms * 32 + strpos(self::ALPHABET, $char);
        }
        return $ms;
    }

    private static function encodeTime(int $ms): string
    {
        $out = '';
        for ($i = 9; $i >= 0; $i--) {
            $mod = $ms % 32;
            $out = self::ALPHABET[$mod] . $out;
            $ms = intdiv($ms, 32);
        }
        return $out;
    }

    /** @return list<int> 16 random byte values (0-255) */
    private static function randomBytes16(): array
    {
        return array_values(unpack('C*', random_bytes(16)) ?: []);
    }

    /**
     * @param list<int> $bytes
     * @return list<int>
     */
    private static function incrementRandom(array $bytes): array
    {
        for ($i = count($bytes) - 1; $i >= 0; $i--) {
            if ($bytes[$i] < 255) {
                $bytes[$i]++;
                return $bytes;
            }
            $bytes[$i] = 0;
        }
        return self::randomBytes16(); // overflow (astronomically unlikely)
    }

    /** @param list<int> $bytes 16 bytes -> 16 base32 chars (80 bits, padded) */
    private static function encodeRandom(array $bytes): string
    {
        // Treat the 16 bytes as a big-endian integer, emit 16 base32 digits.
        $bits = '';
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }
        // 128 bits -> take low 80 bits for the random component (16 chars * 5).
        $bits = substr($bits, -80);
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec($chunk)];
        }
        return $out;
    }
}
