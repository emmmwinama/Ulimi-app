<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

/**
 * Requires an authenticated, active customer user. Unauthenticated requests get
 * a 401 JSON body (API/XHR) or a redirect to the login page with the intended
 * URL stashed for post-login return.
 */
final class Authenticate implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        if ($request->wantsJson()) {
            return Response::json(['error' => 'Unauthenticated.'], 401);
        }

        Session::instance()->put('_intended', $request->path);
        Flash::info('Please sign in to continue.');
        return Response::redirect(url('login'));
    }
}
