<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\FarmRepository;

final class ApiFarmController
{
    public function __construct(private readonly FarmRepository $farms = new FarmRepository())
    {
    }

    /** Every farm the user can act on, and which one this request is scoped to. */
    public function context(Request $request): Response
    {
        $farms = $this->farms->forUser((string) ApiAuth::id());
        $ctx = FarmContext::current();

        return Response::json([
            'active_farm_id' => $ctx->farmId(),
            'role' => $ctx->authz->role,
            'farms' => array_map(static fn (array $f): array => [
                'id' => $f['id'], 'name' => $f['name'], 'location' => $f['location'],
                'role' => $f['member_role'],
            ], $farms),
        ]);
    }
}
