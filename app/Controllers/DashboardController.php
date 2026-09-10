<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CropFieldRepository;
use App\Repositories\FarmMemberRepository;
use App\Repositories\FieldRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $farmId = $ctx->farmId();
        $db = Database::instance();

        $fields = new FieldRepository();
        $crops = new CropFieldRepository();

        // Harvests expected in the next 30 days for still-active plantings.
        $upcoming = $db->select(
            "SELECT cf.id, cf.expected_harvest_date, cf.variety, ct.name AS crop_name, f.name AS field_name
             FROM crop_fields cf
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE cf.farm_id = :fid AND cf.is_archived = 0 AND cf.status = 'Active'
               AND cf.expected_harvest_date >= CURDATE()
               AND cf.expected_harvest_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY cf.expected_harvest_date ASC
             LIMIT 8",
            ['fid' => $farmId],
        );

        return $this->view('pages/dashboard', [
            'title'        => 'Dashboard',
            'active'       => 'dashboard',
            'ctx'          => $ctx,
            'subscription' => $ctx->subscription,
            'stats'        => [
                'fields'      => $fields->countForFarm($farmId),
                'total_area'  => $fields->totalArea($farmId),
                'active_crops'=> $crops->countActive($farmId),
                'team'        => (new FarmMemberRepository())->countActive($farmId),
                'seasons'     => count($crops->seasons($farmId)),
            ],
            'upcoming'     => $upcoming,
        ]);
    }
}
