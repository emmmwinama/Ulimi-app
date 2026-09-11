<?php

declare(strict_types=1);

/**
 * Front controller — the single entry point for every web request.
 *
 * Apache/Nginx rewrites all non-file paths here (see public/.htaccess).
 * Nothing else in the tree is web-reachable when DocumentRoot points at
 * this directory.
 */

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

/**
 * Under `php -S` (the dev server) this file is the router script, so it must
 * hand back real files itself. Apache/Nginx serve them before PHP is reached.
 */
if (PHP_SAPI === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path !== false && $path !== __FILE__ && is_file($path) && str_starts_with($path, __DIR__)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

$request = Request::capture();

// CORS preflight for the mobile API. Handled here rather than as a routed
// OPTIONS handler per endpoint: the router only matches methods that are
// explicitly registered, and every /api/mobile/* path needs the same
// preflight response regardless of which endpoint it targets.
if ($request->method === 'OPTIONS' && str_starts_with($request->path, '/api/mobile/')) {
    (new App\Middleware\CorsMobile())->handle($request, static fn () => Response::noContent(204))->send();
    return;
}

// Force HTTPS in production (defence in depth; the host/CDN should already do this).
if (
    !$request->isSecure()
    && (string) App\Core\Config::get('app.env') === 'production'
    && $request->method === 'GET'
) {
    $target = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    Response::redirect($target, 301)->send();
    return;
}

Session::instance()->boot($request);
Session::instance()->ageFlash();

/** @var Router $router */
$router = require dirname(__DIR__) . '/app/routes.php';

$response = $router->dispatch($request);
$response->send();

// Best-effort: hand the response to the client, then flush queued email.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
try {
    App\Core\Mailer::flush(5);
    App\Core\RateLimiter::instance()->purgeExpired();
} catch (Throwable $e) {
    App\Core\Logger::instance()->warning('Post-response tasks failed: {msg}', ['msg' => $e->getMessage()]);
}
