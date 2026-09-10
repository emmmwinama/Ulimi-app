<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Authz;
use App\Core\FarmContext;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\FarmMemberRepository;
use App\Repositories\FarmRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\SubscriptionService;
use Closure;

/**
 * Establishes the active farm for the request and the caller's rights over it.
 *
 * - Active farm id comes from the session, never from request input.
 * - Membership is re-verified against the database on every request; a stale or
 *   revoked membership silently falls back to the user's first valid farm.
 * - A user with no farm is routed to the create-farm screen.
 * - The subscription is reconciled (lazy expiry) here so every downstream page
 *   sees a current status.
 *
 * Runs after Authenticate, so Auth::user() is guaranteed non-null.
 */
final class ResolveFarmContext implements Middleware
{
    public function __construct(
        private readonly FarmRepository $farms = new FarmRepository(),
        private readonly FarmMemberRepository $members = new FarmMemberRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
        private readonly SubscriptionService $subscriptionService = new SubscriptionService(),
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (string) Auth::id();
        $session = Session::instance();

        $selectedFarmId = $session->get('farm_id');
        $membership = null;

        if (is_string($selectedFarmId) && $selectedFarmId !== '') {
            $membership = $this->members->membership($userId, $selectedFarmId);
        }

        if ($membership === null) {
            $available = $this->farms->forUser($userId);
            if ($available === []) {
                if (str_starts_with($request->path, '/onboarding')) {
                    return $next($request); // let the create-farm flow through
                }
                return Response::redirect(url('onboarding/farm'));
            }
            $selectedFarmId = (string) $available[0]['id'];
            $membership = $this->members->membership($userId, $selectedFarmId);
            if ($membership === null) {
                // Data inconsistency; treat as no access.
                Auth::logout();
                return Response::redirect(url('login'));
            }
            $session->put('farm_id', $selectedFarmId);
        }

        $farm = $this->farms->find($selectedFarmId);
        if ($farm === null) {
            $session->forget('farm_id');
            return Response::redirect(url('dashboard'));
        }

        $subscription = $this->subscriptions->forUserWithTier((string) $farm['user_id']);
        if ($subscription !== null) {
            $subscription = $this->subscriptionService->reconcile($subscription);
        }

        $authz = Authz::fromMember([
            'role'        => (string) $membership['role'],
            'permissions' => $membership['permissions'],
            'farm_id'     => $selectedFarmId,
        ]);

        Authz::setCurrent($authz);
        FarmContext::set($farm, $membership, $authz, $subscription);

        return $next($request);
    }
}
