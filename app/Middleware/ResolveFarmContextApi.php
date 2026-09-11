<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiAuth;
use App\Core\Authz;
use App\Core\FarmContext;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\FarmMemberRepository;
use App\Repositories\FarmRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\SubscriptionService;
use Closure;

/**
 * API equivalent of ResolveFarmContext. There is no session farm switcher for
 * a mobile client, so the active farm comes from the `X-Farm-Id` header
 * (re-verified against membership on every request); with no header, the
 * user's first farm is used. JSON errors throughout — never a redirect.
 */
final class ResolveFarmContextApi implements Middleware
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
        $userId = (string) ApiAuth::id();
        $requestedFarmId = $request->header('X-Farm-Id');

        $membership = $requestedFarmId !== null
            ? $this->members->membership($userId, $requestedFarmId)
            : null;

        $farmId = $requestedFarmId;
        if ($membership === null) {
            $available = $this->farms->forUser($userId);
            if ($available === []) {
                return Response::json(['error' => 'No farm found for this account.'], 404);
            }
            $farmId = (string) $available[0]['id'];
            $membership = $this->members->membership($userId, $farmId);
            if ($membership === null) {
                return Response::json(['error' => 'No farm access.'], 403);
            }
        }

        $farm = $this->farms->find((string) $farmId);
        if ($farm === null) {
            return Response::json(['error' => 'Farm not found.'], 404);
        }

        $subscription = $this->subscriptions->forUserWithTier((string) $farm['user_id']);
        if ($subscription !== null) {
            $subscription = $this->subscriptionService->reconcile($subscription);
        }

        $authz = Authz::fromMember([
            'role' => (string) $membership['role'],
            'permissions' => $membership['permissions'],
            'farm_id' => (string) $farmId,
        ]);
        Authz::setCurrent($authz);
        FarmContext::set($farm, $membership, $authz, $subscription);

        return $next($request);
    }
}
