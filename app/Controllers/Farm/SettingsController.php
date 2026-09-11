<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\FarmRepository;

/**
 * Farm profile settings and the danger zone (delete farm). Deletion is
 * owner-only and requires the farm's name typed back exactly — the same
 * "type to confirm" pattern used by every serious SaaS for an irreversible,
 * cascading action.
 */
final class SettingsController extends Controller
{
    public function __construct(private readonly FarmRepository $farms = new FarmRepository())
    {
    }

    public function edit(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('settings/index', [
            'title'    => 'Farm settings',
            'active'   => 'settings',
            'farm'     => $ctx->farm,
            'isOwner'  => $ctx->authz->isOwner(),
        ]);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('farm.manage')) {
            Flash::error('Only the farm owner can change these settings.');
            return $this->redirect(url('settings'));
        }

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

        $this->farms->update($ctx->farmId(), [
            'name'         => trim((string) $data['name']),
            'location'     => trim((string) $data['location']),
            'owner_name'   => isset($data['owner_name']) && $data['owner_name'] !== '' ? trim((string) $data['owner_name']) : null,
            'location_lat' => isset($data['location_lat']) && $data['location_lat'] !== '' ? (float) $data['location_lat'] : null,
            'location_lng' => isset($data['location_lng']) && $data['location_lng'] !== '' ? (float) $data['location_lng'] : null,
        ]);

        AuditLog::user('farm.settings_updated', (string) Auth::id(), [], $ctx->farmId(), $request->ip());
        Flash::success('Farm settings saved.');
        return $this->redirect(url('settings'));
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->authz->isOwner()) {
            Flash::error('Only the farm owner can delete this farm.');
            return $this->redirect(url('settings'));
        }

        $confirm = trim((string) $request->input('confirm_name', ''));
        if ($confirm !== trim($ctx->farmName())) {
            return $this->fieldError($request, 'confirm_name', 'Type the farm name exactly to confirm deletion.');
        }

        $farmId = $ctx->farmId();
        Database::instance()->delete('farms', ['id' => $farmId]);
        AuditLog::user('farm.deleted', (string) Auth::id(), ['farm_id' => $farmId], null, $request->ip());

        Session::instance()->forget('farm_id');
        Flash::success('The farm and all its records have been deleted.');
        return $this->redirect(url('dashboard'));
    }
}
