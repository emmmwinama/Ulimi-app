<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Middleware;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Fixed-window throttle keyed by "<name>:<client-ip>". Configured via the route
 * spec suffix, e.g. `Throttle:login` or `Throttle:form`.
 *
 * Named profiles resolve their limits from config('security.*'):
 *   login -> login_max_attempts / login_decay_seconds
 *   form  -> form_max_attempts  / form_decay_seconds
 *
 * Only non-idempotent (write) requests consume budget; GETs pass through so a
 * user re-opening a form is never locked out.
 */
final class Throttle implements Middleware
{
    private int $maxAttempts;
    private int $decaySeconds;
    private string $name;

    public function __construct(string $profile = 'form')
    {
        $this->name = preg_replace('/[^a-z0-9_]/i', '', $profile) ?: 'form';

        [$maxKey, $decayKey, $maxDefault, $decayDefault] = match ($this->name) {
            'login' => ['security.login_max_attempts', 'security.login_decay_seconds', 5, 900],
            default => ['security.form_max_attempts', 'security.form_decay_seconds', 8, 3600],
        };

        $this->maxAttempts   = (int) Config::get($maxKey, $maxDefault);
        $this->decaySeconds  = (int) Config::get($decayKey, $decayDefault);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $limiter = RateLimiter::instance();
        $bucket = $this->name . ':' . $request->ip();

        if ($limiter->tooManyAttempts($bucket, $this->maxAttempts)) {
            return $this->lockedOut($request, $limiter->availableIn($bucket));
        }

        if (!$request->isReading()) {
            $limiter->hit($bucket, $this->decaySeconds);
        }

        return $next($request);
    }

    private function lockedOut(Request $request, int $retryAfter): Response
    {
        $minutes = max(1, (int) ceil($retryAfter / 60));
        if ($request->wantsJson()) {
            return Response::json(['error' => 'Too many attempts. Try again later.'], 429)
                ->header('Retry-After', (string) $retryAfter);
        }
        return Response::view('errors/429', ['minutes' => $minutes], 429)
            ->header('Retry-After', (string) $retryAfter);
    }
}
