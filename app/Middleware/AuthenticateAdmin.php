<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\AdminAuth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * Guards the /admin area. Separate session namespace from customer Auth, so the
 * two credentials never substitute for one another.
 */
final class AuthenticateAdmin implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AdminAuth::check()) {
            return $next($request);
        }

        if ($request->wantsJson()) {
            return Response::json(['error' => 'Unauthenticated.'], 401);
        }

        Session::instance()->put('_admin_intended', $request->path);
        return Response::redirect(url('admin/login'));
    }
}
