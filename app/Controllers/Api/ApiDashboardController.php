<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CropFieldRepository;
use App\Repositories\FieldRepository;
use App\Services\FinanceSummary;

final class ApiDashboardController
{
    public function __construct(
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly FinanceSummary $finance = new FinanceSummary(),
    ) {
    }

    public function show(Request $request): Response
    {
        $ctx = FarmContext::current();
        $farmId = $ctx->farmId();
        $totals = $this->finance->totals($farmId);

        $recentActivities = Database::instance()->select(
            'SELECT a.id, a.activity_type, a.date, f.name AS field_name
             FROM farm_activities a JOIN fields f ON f.id = a.field_id
             WHERE a.farm_id = :fid ORDER BY a.date DESC, a.created_at DESC LIMIT 10',
            ['fid' => $farmId],
        );

        return Response::json([
            'farm' => ['id' => $farmId, 'name' => $ctx->farmName()],
            'metrics' => [
                'fields' => $this->fields->countForFarm($farmId),
                'total_area_ha' => $this->fields->totalArea($farmId),
                'active_crops' => $this->crops->countActive($farmId),
                'net_income' => $totals['net'],
                'income' => $totals['income'],
                'cost' => $totals['total_cost'],
            ],
            'recent_activities' => $recentActivities,
            'subscription_status' => $ctx->subscriptionStatus(),
            'read_only' => $ctx->isReadOnly(),
        ]);
    }
}
