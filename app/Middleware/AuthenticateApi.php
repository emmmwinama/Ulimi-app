<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiAuth;
use App\Core\Database;
use App\Core\Jwt;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Verifies the Bearer JWT on a mobile API request and populates ApiAuth. The
 * user is re-read from the database on every request (not just trusted from
 * the token claims) so a deactivated account is rejected immediately even
 * with an unexpired access token.
 */
final class AuthenticateApi implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $claims = $token !== null ? Jwt::decode($token) : null;

        if ($claims === null || !isset($claims['sub']) || ($claims['type'] ?? null) !== 'access') {
            return Response::json(['error' => 'Unauthenticated.'], 401);
        }

        $user = Database::instance()->selectOne(
            'SELECT * FROM users WHERE id = :id LIMIT 1',
            ['id' => (string) $claims['sub']],
        );
        if ($user === null || (int) $user['is_active'] !== 1) {
            return Response::json(['error' => 'Unauthenticated.'], 401);
        }

        ApiAuth::set($user);
        return $next($request);
    }
}
