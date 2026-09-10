<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Contract for a pipeline middleware.
 *
 * Implementations either return their own Response (short-circuit) or call
 * `$next($request)` and return/adjust the downstream Response.
 */
interface Middleware
{
    public function handle(Request $request, Closure $next): Response;
}
