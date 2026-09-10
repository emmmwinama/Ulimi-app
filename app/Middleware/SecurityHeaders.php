<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Adds defence-in-depth response headers to every route. Ported from the
 * original Next.js middleware.ts, tightened because all assets are self-hosted.
 */
final class SecurityHeaders implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'DENY',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'camera=(), microphone=(), geolocation=(self), payment=(), usb=()',
            'Content-Security-Policy'  => (string) Config::get('security.csp', "default-src 'self'"),
            'Cross-Origin-Opener-Policy'   => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        $path = $request->path;
        if (
            str_starts_with($path, '/dashboard')
            || str_starts_with($path, '/admin')
            || str_starts_with($path, '/api')
            || str_starts_with($path, '/account')
        ) {
            $headers['X-Robots-Tag'] = 'noindex, nofollow, noarchive';
            $headers['Cache-Control'] = 'no-store, max-age=0';
        }

        if ($request->isSecure() && (string) Config::get('app.env') === 'production') {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        // Do not clobber a header a controller set deliberately.
        foreach ($headers as $name => $value) {
            if (!array_key_exists($name, $response->getHeaders())) {
                $response->header($name, $value);
            }
        }

        return $response;
    }
}
