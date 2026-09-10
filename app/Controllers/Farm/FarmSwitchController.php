<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\FarmMemberRepository;

final class FarmSwitchController extends Controller
{
    public function switch(Request $request): Response
    {
        $farmId = (string) $request->input('farm_id', '');
        $userId = (string) Auth::id();

        // Authorisation: the target farm must be one the user actually belongs to.
        $membership = (new FarmMemberRepository())->membership($userId, $farmId);
        if ($membership === null) {
            Flash::error('You do not have access to that farm.');
            return $this->redirect(url('dashboard'));
        }

        Session::instance()->put('farm_id', $farmId);
        Flash::success('Switched farm.');
        return $this->redirect(url('dashboard'));
    }
}
