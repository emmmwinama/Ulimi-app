<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\FarmContext;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Route-level permission gate. Spec form: `Can:finance.manage`.
 * Requires a resolved FarmContext (place after ResolveFarmContext).
 *
 * Also enforces the read-only lock for write abilities when the subscription
 * has lapsed: an ability ending in `.manage` is refused if the context is
 * read-only, even for the owner.
 */
final class Can implements Middleware
{
    public function __construct(private readonly string $ability = '')
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $ctx = FarmContext::current();

        if ($this->ability === '' || !$ctx->can($this->ability)) {
            return $this->deny($request, 'You do not have permission to do that.');
        }

        if (str_ends_with($this->ability, '.manage') && $ctx->isReadOnly()) {
            return $this->deny(
                $request,
                'Your subscription is not active. This account is read-only until it is renewed.',
            );
        }

        return $next($request);
    }

    private function deny(Request $request, string $message): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => $message], 403);
        }
        return Response::view('errors/403', ['message' => $message], 403);
    }
}
