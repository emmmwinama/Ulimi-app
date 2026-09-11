<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
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
        $user = Auth::user() ?? [];

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

        $totalFields = $fields->countForFarm($farmId);
        $totalArea = $fields->totalArea($farmId);

        $cropStatusCounts = $db->select(
            "SELECT status, COUNT(*) AS n FROM crop_fields WHERE farm_id = :fid AND is_archived = 0 GROUP BY status",
            ['fid' => $farmId],
        );
        $activeCrops = 0;
        $harvestedCrops = 0;
        foreach ($cropStatusCounts as $row) {
            if ($row['status'] === 'Active') {
                $activeCrops = (int) $row['n'];
            } elseif ($row['status'] === 'Harvested') {
                $harvestedCrops = (int) $row['n'];
            }
        }

        $totalYieldKg = (float) $db->scalar(
            "SELECT COALESCE(SUM(hy.quantity_kg), 0)
             FROM harvest_yields hy JOIN crop_fields cf ON cf.id = hy.crop_field_id
             WHERE cf.farm_id = :fid",
            ['fid' => $farmId],
        );

        $income = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE farm_id = :fid AND type = 'Income'",
            ['fid' => $farmId],
        );
        $expense = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE farm_id = :fid AND type = 'Expense'",
            ['fid' => $farmId],
        );

        $labourCost = (float) $db->scalar(
            "SELECT COALESCE(SUM(al.total_cost), 0) FROM activity_labour al
             JOIN farm_activities fa ON fa.id = al.activity_id WHERE fa.farm_id = :fid",
            ['fid' => $farmId],
        );
        $inputCost = (float) $db->scalar(
            "SELECT COALESCE(SUM(ai.total_cost), 0) FROM activity_inputs ai
             JOIN farm_activities fa ON fa.id = ai.activity_id WHERE fa.farm_id = :fid",
            ['fid' => $farmId],
        );
        $otherCost = (float) $db->scalar(
            "SELECT COALESCE(SUM(aoc.amount), 0) FROM activity_other_costs aoc
             JOIN farm_activities fa ON fa.id = aoc.activity_id WHERE fa.farm_id = :fid",
            ['fid' => $farmId],
        );
        $totalActivityCost = $labourCost + $inputCost + $otherCost;

        $totalOverhead = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM overhead_expenses WHERE farm_id = :fid",
            ['fid' => $farmId],
        );
        $totalInventoryItems = (int) $db->scalar(
            "SELECT COUNT(*) FROM inventory_items WHERE farm_id = :fid",
            ['fid' => $farmId],
        );
        $net = $income - $totalActivityCost - $totalOverhead - $expense;

        // Season profitability: crop-field area/count, activity cost, and
        // transaction income/expense, all grouped by season and merged.
        $seasonMap = [];
        foreach ($db->select(
            "SELECT season, COALESCE(SUM(area_planted), 0) AS area
             FROM crop_fields WHERE farm_id = :fid AND is_archived = 0 GROUP BY season",
            ['fid' => $farmId],
        ) as $row) {
            $seasonMap[$row['season']] = ['area' => (float) $row['area'], 'activityCost' => 0.0, 'income' => 0.0, 'expense' => 0.0];
        }
        foreach ($db->select(
            "SELECT cf.season, COALESCE(SUM(al.total_cost), 0) AS cost
             FROM crop_fields cf JOIN farm_activities fa ON fa.crop_field_id = cf.id
             JOIN activity_labour al ON al.activity_id = fa.id
             WHERE cf.farm_id = :fid GROUP BY cf.season",
            ['fid' => $farmId],
        ) as $row) {
            $seasonMap[$row['season']]['activityCost'] = ($seasonMap[$row['season']]['activityCost'] ?? 0.0) + (float) $row['cost'];
        }
        foreach ($db->select(
            "SELECT cf.season, COALESCE(SUM(ai.total_cost), 0) AS cost
             FROM crop_fields cf JOIN farm_activities fa ON fa.crop_field_id = cf.id
             JOIN activity_inputs ai ON ai.activity_id = fa.id
             WHERE cf.farm_id = :fid GROUP BY cf.season",
            ['fid' => $farmId],
        ) as $row) {
            $seasonMap[$row['season']]['activityCost'] = ($seasonMap[$row['season']]['activityCost'] ?? 0.0) + (float) $row['cost'];
        }
        foreach ($db->select(
            "SELECT cf.season, COALESCE(SUM(aoc.amount), 0) AS cost
             FROM crop_fields cf JOIN farm_activities fa ON fa.crop_field_id = cf.id
             JOIN activity_other_costs aoc ON aoc.activity_id = fa.id
             WHERE cf.farm_id = :fid GROUP BY cf.season",
            ['fid' => $farmId],
        ) as $row) {
            $seasonMap[$row['season']]['activityCost'] = ($seasonMap[$row['season']]['activityCost'] ?? 0.0) + (float) $row['cost'];
        }
        foreach ($db->select(
            "SELECT season, type, COALESCE(SUM(amount), 0) AS amt
             FROM transactions WHERE farm_id = :fid AND season IS NOT NULL AND season != ''
             GROUP BY season, type",
            ['fid' => $farmId],
        ) as $row) {
            if (!isset($seasonMap[$row['season']])) {
                $seasonMap[$row['season']] = ['area' => 0.0, 'activityCost' => 0.0, 'income' => 0.0, 'expense' => 0.0];
            }
            if ($row['type'] === 'Income') {
                $seasonMap[$row['season']]['income'] = (float) $row['amt'];
            } else {
                $seasonMap[$row['season']]['expense'] = (float) $row['amt'];
            }
        }
        $seasons = [];
        foreach ($seasonMap as $name => $s) {
            $seasons[] = [
                'name'       => (string) $name,
                'netRevenue' => $s['income'] - $s['activityCost'] - $s['expense'],
            ];
        }
        usort($seasons, static fn (array $a, array $b): int => strcmp((string) $b['name'], (string) $a['name']));

        // Crop area mix + status badges.
        $cropRows = $db->select(
            "SELECT ct.name, cf.area_planted, cf.status
             FROM crop_fields cf JOIN crop_types ct ON ct.id = cf.crop_type_id
             WHERE cf.farm_id = :fid AND cf.is_archived = 0",
            ['fid' => $farmId],
        );
        $cropMap = [];
        foreach ($cropRows as $row) {
            $name = (string) $row['name'];
            $cropMap[$name] ??= ['name' => $name, 'totalArea' => 0.0, 'statuses' => []];
            $cropMap[$name]['totalArea'] += (float) $row['area_planted'];
            $cropMap[$name]['statuses'][] = (string) $row['status'];
        }
        $cropSummary = array_values($cropMap);
        usort($cropSummary, static fn (array $a, array $b): int => $b['totalArea'] <=> $a['totalArea']);

        // Land utilisation per field.
        $fieldLandUse = [];
        foreach ($db->select("SELECT id, name, cultivatable_area FROM fields WHERE farm_id = :fid", ['fid' => $farmId]) as $f) {
            $allocated = (float) $db->scalar(
                "SELECT COALESCE(SUM(area_planted), 0) FROM crop_fields
                 WHERE field_id = :field AND status = 'Active' AND is_archived = 0",
                ['field' => $f['id']],
            );
            $fieldLandUse[] = [
                'name'             => (string) $f['name'],
                'cultivatableArea' => (float) $f['cultivatable_area'],
                'allocated'        => $allocated,
            ];
        }

        $recentActivities = $db->select(
            "SELECT fa.id, fa.activity_type, fa.date, f.name AS field_name, ct.name AS crop_name
             FROM farm_activities fa
             JOIN fields f ON f.id = fa.field_id
             LEFT JOIN crop_fields cf ON cf.id = fa.crop_field_id
             LEFT JOIN crop_types ct ON ct.id = cf.crop_type_id
             WHERE fa.farm_id = :fid
             ORDER BY fa.date DESC, fa.created_at DESC
             LIMIT 5",
            ['fid' => $farmId],
        );

        return $this->view('pages/dashboard', [
            'title'        => 'Dashboard',
            'active'       => 'dashboard',
            'ctx'          => $ctx,
            'subscription' => $ctx->subscription,
            'userName'     => (string) ($user['name'] ?? ''),
            'stats'        => [
                'fields'              => $totalFields,
                'total_area'          => $totalArea,
                'active_crops'        => $activeCrops,
                'harvested_crops'     => $harvestedCrops,
                'seasons'             => count($crops->seasons($farmId)),
                'team'                => (new FarmMemberRepository())->countActive($farmId),
                'total_yield_kg'      => $totalYieldKg,
                'income'              => $income,
                'expense'             => $expense,
                'total_activity_cost' => $totalActivityCost,
                'total_overhead'      => $totalOverhead,
                'total_inventory'     => $totalInventoryItems,
                'net'                 => $net,
            ],
            'seasonProfit' => $seasons,
            'cropSummary'  => $cropSummary,
            'fieldLandUse' => $fieldLandUse,
            'recentActivities' => $recentActivities,
            'upcoming'     => $upcoming,
        ]);
    }
}
