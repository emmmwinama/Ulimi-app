<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Rejects state-changing requests without a valid CSRF token. GET/HEAD/OPTIONS
 * are exempt (they must remain side-effect free by convention).
 *
 * The token comes from the `_token` form field or the `X-CSRF-Token` header and
 * is compared in constant time against the per-session secret.
 */
final class VerifyCsrf implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isReading()) {
            return $next($request);
        }

        if (!Csrf::check(Csrf::fromRequest($request))) {
            if ($request->wantsJson()) {
                return Response::json(['error' => 'CSRF token mismatch.'], 419);
            }
            return Response::view('errors/419', [], 419);
        }

        return $next($request);
    }
}
