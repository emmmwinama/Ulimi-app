<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * CORS for the mobile API. Allowlist-based (never a bare `*`): only the
 * origins the mobile app actually runs from during development (Expo's
 * local dev server) get the header. A packaged mobile app doesn't send an
 * `Origin` header at all, so production API calls from the built app are
 * unaffected by this allowlist either way.
 */
final class CorsMobile implements Middleware
{
    private const ALLOWED_ORIGINS = [
        'http://localhost:8081',
        'http://127.0.0.1:8081',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $origin = (string) $request->header('Origin', '');
        $allowOrigin = in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : '';

        if ($request->method === 'OPTIONS') {
            $response = Response::noContent(204);
        } else {
            $response = $next($request);
        }

        if ($allowOrigin !== '') {
            $response->header('Access-Control-Allow-Origin', $allowOrigin);
        }
        $response->header('Vary', 'Origin');
        $response->header('Access-Control-Allow-Methods', 'GET,POST,PATCH,DELETE,OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type,Authorization,X-Farm-Id');

        return $response;
    }
}
