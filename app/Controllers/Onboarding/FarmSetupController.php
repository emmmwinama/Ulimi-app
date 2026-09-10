<?php

declare(strict_types=1);

namespace App\Controllers\Onboarding;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\FarmMemberRepository;
use App\Repositories\FarmRepository;
use App\Repositories\SubscriptionRepository;

/**
 * First-run farm creation, and the "add another farm" entry point. Enforces the
 * subscription's max_farms limit for the owning account.
 */
final class FarmSetupController extends Controller
{
    public function __construct(
        private readonly FarmRepository $farms = new FarmRepository(),
        private readonly FarmMemberRepository $members = new FarmMemberRepository(),
        private readonly SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    public function show(Request $request): Response
    {
        $userId = (string) Auth::id();
        $existing = $this->farms->countForOwner($userId);

        return $this->view('onboarding/farm', [
            'title'   => $existing === 0 ? 'Set up your farm' : 'Add a farm',
            'isFirst' => $existing === 0,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'         => ['required', 'max:160'],
            'location'     => ['required', 'max:160'],
            'owner_name'   => ['max:120'],
            'location_lat' => ['numeric'],
            'location_lng' => ['numeric'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $userId = (string) Auth::id();

        $sub = $this->subscriptions->forUserWithTier($userId);
        $maxFarms = $sub !== null ? (int) $sub['max_farms'] : 1;
        $ownedFarms = $this->farms->countForOwner($userId);

        if ($maxFarms !== -1 && $ownedFarms >= $maxFarms) {
            Flash::error('Your plan allows ' . $maxFarms . ' farm' . ($maxFarms === 1 ? '' : 's') . '. Upgrade to add more.');
            return $this->redirect(url('dashboard'));
        }

        $farmId = Database::instance()->transaction(function () use ($data, $userId): string {
            $fid = $this->farms->create([
                'name'         => trim((string) $data['name']),
                'location'     => trim((string) $data['location']),
                'owner_name'   => isset($data['owner_name']) ? trim((string) $data['owner_name']) : null,
                'location_lat' => isset($data['location_lat']) && $data['location_lat'] !== '' ? (float) $data['location_lat'] : null,
                'location_lng' => isset($data['location_lng']) && $data['location_lng'] !== '' ? (float) $data['location_lng'] : null,
                'user_id'      => $userId,
            ]);

            $this->members->create([
                'farm_id' => $fid,
                'user_id' => $userId,
                'role'    => 'owner',
                'status'  => 'active',
            ]);

            return $fid;
        });

        Session::instance()->put('farm_id', $farmId);
        AuditLog::user('farm.created', $userId, ['farm_id' => $farmId], $farmId, $request->ip());

        Flash::success('Your farm is ready.');
        return $this->redirect(url('dashboard'));
    }
}
