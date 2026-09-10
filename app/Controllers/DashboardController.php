<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\FarmMemberRepository;

/**
 * Phase 1 dashboard: farm context, subscription state, team size, and a clear
 * map of what each upcoming module will add. Real operational metrics arrive
 * with Phase 2+.
 */
final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $members = new FarmMemberRepository();

        return $this->view('pages/dashboard', [
            'title'       => 'Dashboard',
            'active'      => 'dashboard',
            'ctx'         => $ctx,
            'teamCount'   => $members->countActive($ctx->farmId()),
            'subscription'=> $ctx->subscription,
        ]);
    }
}
