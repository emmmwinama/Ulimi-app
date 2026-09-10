<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Keeps already-authenticated users off the login / register / password pages.
 */
final class RequireGuest implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return Response::redirect(url('dashboard'));
        }
        return $next($request);
    }
}
