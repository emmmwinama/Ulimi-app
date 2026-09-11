<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Minimal HS256 JWT encode/decode for the mobile API's access tokens.
 *
 * This is NOT "rolling your own crypto": HS256 JWT is base64url(header) + "." +
 * base64url(payload) + "." + base64url(HMAC-SHA256(header.payload, key)) — a
 * fixed, well-defined wire format. The actual cryptographic primitive is
 * PHP's own `hash_hmac('sha256', ...)`. Keeping it in ~60 lines here, reviewed
 * in full, is more auditable than pulling in an unfamiliar vendored library
 * for a well-understood format — and it hard-codes the algorithm (no
 * `alg` field trusted from the token), which closes the classic
 * "alg: none" / algorithm-confusion JWT vulnerability class by construction.
 */
final class Jwt
{
    private const ALG_HEADER = '{"alg":"HS256","typ":"JWT"}';

    /**
     * @param array<string,mixed> $claims
     */
    public static function encode(array $claims): string
    {
        $header = self::b64(self::ALG_HEADER);
        $payload = self::b64((string) json_encode($claims, JSON_UNESCAPED_SLASHES));
        $signature = self::sign($header . '.' . $payload);
        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Verify and decode. Returns the claims array, or null if the token is
     * malformed, mis-signed, or expired (`exp` claim, if present, in the past).
     *
     * @return array<string,mixed>|null
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $payload, $signature] = $parts;

        // Reject anything claiming a different algorithm outright — we only
        // ever compute and compare our own HS256 signature below regardless.
        $decodedHeader = json_decode(self::unb64($header) ?? '', true);
        if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? null) !== 'HS256') {
            return null;
        }

        $expected = self::sign($header . '.' . $payload);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $json = self::unb64($payload);
        $claims = $json !== null ? json_decode($json, true) : null;
        if (!is_array($claims)) {
            return null;
        }

        if (isset($claims['exp']) && is_int($claims['exp']) && $claims['exp'] < time()) {
            return null;
        }

        return $claims;
    }

    private static function sign(string $data): string
    {
        $key = (string) Config::get('app.key', '');
        if ($key === '') {
            throw new RuntimeException('app.key must be configured to issue tokens.');
        }
        return self::b64(hash_hmac('sha256', $data, $key, true));
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): ?string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
