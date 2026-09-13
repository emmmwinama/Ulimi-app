<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\CooperativeContext;
use App\Core\FarmContext;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CooperativeRepository;
use Closure;

/**
 * Establishes the active cooperative for the request, scoped to the
 * already-resolved active farm (this always runs after ResolveFarmContext).
 *
 * The cooperative id comes from the route ({id}), never from a session or
 * request body value — every request re-verifies that the active farm is
 * actually a member before granting access, exactly like FarmContext does
 * for farm membership. A farm that isn't a member gets a 404, the same
 * enumeration-safe treatment every other farm-scoped resource gets on a
 * guessed/copied id.
 */
final class ResolveCooperativeContext implements Middleware
{
    public function __construct(private readonly CooperativeRepository $coops = new CooperativeRepository())
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $coopId = (string) $request->route('id');
        $farmId = FarmContext::current()->farmId();

        $cooperative = $this->coops->find($coopId);
        $membership = $cooperative !== null ? $this->coops->membership($coopId, $farmId) : null;

        if ($cooperative === null || $membership === null) {
            return Response::view('errors/404', [], 404);
        }

        CooperativeContext::set($cooperative, $membership);

        return $next($request);
    }
}
