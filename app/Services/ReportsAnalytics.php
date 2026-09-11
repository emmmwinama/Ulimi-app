<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Full reports-dashboard data set — a PHP port of the reference Next.js
 * app's /api/reports + /api/reports/trends routes, combined into one pass
 * since they share almost all of their inputs (crop-field cost/yield/
 * revenue rows). Overhead is allocated once, over ALL crops regardless of
 * the display filter, so a crop's overhead share never changes when the
 * viewer switches between Active/Archived/Both — matching the reference's
 * explicit design (see the "How overhead is allocated" copy on the page).
 */
final class ReportsAnalytics
{
    private function db(): Database
    {
        return Database::instance();
    }

    /**
     * @param array{season:string,archived:string,field_id:string,crop_field_id:string,from:string,to:string} $filters
     * @return array<string,mixed>
     */
    public function build(string $farmId, array $filters): array
    {
        $season = $filters['season'] !== '' ? $filters['season'] : null;
        $archived = $filters['archived'] !== '' ? $filters['archived'] : 'active';
        $fieldId = $filters['field_id'] !== '' ? $filters['field_id'] : null;
        $cropFieldId = $filters['crop_field_id'] !== '' ? $filters['crop_field_id'] : null;
        $from = $filters['from'] !== '' ? $filters['from'] : null;
        $to = $filters['to'] !== '' ? $filters['to'] : null;
        $hasDateFilter = $from !== null || $to !== null;

        $allCrops = $this->loadAllCrops($farmId);
        $activities = $this->loadActivityCosts($farmId);
        $yields = $this->loadYields($farmId);
        $transactionsAll = $this->loadTransactions($farmId);
        $overheads = $this->loadOverheads($farmId, $hasDateFilter ? $from : null, $hasDateFilter ? $to : null);
        $totalOverhead = array_sum(array_column($overheads, 'amount'));

        // ---- overhead allocation: fixed, based on ALL crops (never the display filter) ----
        $overheadAllocation = [];
        $totalAllocated = 0.0;
        $totalUnallocated = 0.0;
        foreach ($overheads as $oh) {
            $growing = array_filter($allCrops, static fn ($c) => $c['planting_date'] <= $oh['date'] && $c['expected_harvest_date'] >= $oh['date']);
            $totalArea = array_sum(array_column($growing, 'area_planted'));
            if ($totalArea <= 0.0) {
                $totalUnallocated += $oh['amount'];
                continue;
            }
            foreach ($growing as $c) {
                $share = ($c['area_planted'] / $totalArea) * $oh['amount'];
                $overheadAllocation[$c['id']] = ($overheadAllocation[$c['id']] ?? 0.0) + $share;
                $totalAllocated += $share;
            }
        }

        $actByCrop = [];
        foreach ($activities as $a) {
            $actByCrop[$a['crop_field_id']][] = $a;
        }
        $yieldsByCrop = [];
        foreach ($yields as $y) {
            $yieldsByCrop[$y['crop_field_id']][] = $y;
        }
        $txByCrop = [];
        foreach ($transactionsAll as $t) {
            if ($t['crop_field_id'] !== null) {
                $txByCrop[$t['crop_field_id']][] = $t;
            }
        }

        $inRange = static function (string $date) use ($from, $to): bool {
            if ($from !== null && $date < $from) {
                return false;
            }
            if ($to !== null && $date > $to) {
                return false;
            }
            return true;
        };

        // ---- apply the display filter ----
        $displayCrops = array_filter($allCrops, static function ($c) use ($season, $archived, $fieldId, $cropFieldId) {
            if ($fieldId !== null && $c['field_id'] !== $fieldId) {
                return false;
            }
            if ($cropFieldId !== null && $c['id'] !== $cropFieldId) {
                return false;
            }
            if ($archived === 'active' && $c['is_archived']) {
                return false;
            }
            if ($archived === 'archived' && !$c['is_archived']) {
                return false;
            }
            if ($season !== null && $c['season'] !== $season) {
                return false;
            }
            return true;
        });

        $cropRows = [];
        foreach ($displayCrops as $c) {
            $acts = $actByCrop[$c['id']] ?? [];
            if ($hasDateFilter) {
                $acts = array_filter($acts, static fn ($a) => $inRange($a['date']));
            }
            $inputCost = array_sum(array_column($acts, 'input_cost'));
            $labourCost = array_sum(array_column($acts, 'labour_cost'));
            $otherCost = array_sum(array_column($acts, 'other_cost'));

            $ylds = $yieldsByCrop[$c['id']] ?? [];
            if ($hasDateFilter) {
                $ylds = array_filter($ylds, static fn ($y) => $inRange($y['harvest_date']));
            }
            $totalYieldKg = array_sum(array_column($ylds, 'quantity_kg'));

            $txs = $txByCrop[$c['id']] ?? [];
            if ($hasDateFilter) {
                $txs = array_filter($txs, static fn ($t) => $inRange($t['date']));
            }
            $revenue = array_sum(array_map(static fn ($t) => $t['type'] === 'Income' ? $t['amount'] : 0.0, $txs));

            $allocatedOverhead = $overheadAllocation[$c['id']] ?? 0.0;
            $activityCost = $inputCost + $labourCost + $otherCost;
            $totalCost = $activityCost + $allocatedOverhead;

            $cropRows[] = [
                'id'                  => $c['id'],
                'is_archived'         => $c['is_archived'],
                'crop_name'           => $c['crop_name'],
                'variety'             => $c['variety'],
                'field_name'          => $c['field_name'],
                'season'              => $c['season'],
                'area_planted'        => $c['area_planted'],
                'planting_date'       => $c['planting_date'],
                'expected_harvest_date' => $c['expected_harvest_date'],
                'activity_count'      => count($acts),
                'input_cost'          => $inputCost,
                'labour_cost'         => $labourCost,
                'other_cost'          => $otherCost,
                'activity_cost'       => $activityCost,
                'allocated_overhead'  => $allocatedOverhead,
                'total_cost'          => $totalCost,
                'revenue'             => $revenue,
                'net_profit'          => $revenue - $totalCost,
                'total_yield_kg'      => $totalYieldKg,
                'yield_per_ha'        => $c['area_planted'] > 0 ? $totalYieldKg / $c['area_planted'] : 0.0,
                'cost_per_ha'         => $c['area_planted'] > 0 ? $totalCost / $c['area_planted'] : 0.0,
                'cost_per_kg'         => $totalYieldKg > 0 ? $totalCost / $totalYieldKg : 0.0,
            ];
        }

        // ---- summary ----
        $totalRevenue = array_sum(array_column($cropRows, 'revenue'));
        $totalExpenses = array_sum(array_column($cropRows, 'total_cost'));
        $totalKgHarvested = array_sum(array_column($cropRows, 'total_yield_kg'));
        $totalArea = array_sum(array_column($cropRows, 'area_planted'));
        $avgYieldPerHa = $totalArea > 0 ? $totalKgHarvested / $totalArea : 0.0;
        $displayedOverhead = array_sum(array_column($cropRows, 'allocated_overhead'));

        $seasonMap = [];
        foreach ($cropRows as $c) {
            $s = $c['season'];
            $seasonMap[$s] ??= ['season' => $s, 'crop_count' => 0, 'archived_count' => 0, 'area' => 0.0, 'expenses' => 0.0, 'revenue' => 0.0];
            $seasonMap[$s]['crop_count']++;
            if ($c['is_archived']) {
                $seasonMap[$s]['archived_count']++;
            }
            $seasonMap[$s]['area'] += $c['area_planted'];
            $seasonMap[$s]['expenses'] += $c['total_cost'];
            $seasonMap[$s]['revenue'] += $c['revenue'];
        }
        $bySeasonBreakdown = array_values($seasonMap);
        usort($bySeasonBreakdown, static fn ($a, $b) => strcmp((string) $b['season'], (string) $a['season']));

        // ---- finance: transactions under the same display filter ----
        $filteredTx = array_values(array_filter($transactionsAll, static function ($t) use ($season, $fieldId, $cropFieldId, $hasDateFilter, $inRange, $archived, $allCrops) {
            if ($season !== null && $t['season'] !== $season) {
                return false;
            }
            if ($fieldId !== null && $t['field_id'] !== $fieldId) {
                return false;
            }
            if ($cropFieldId !== null && $t['crop_field_id'] !== $cropFieldId) {
                return false;
            }
            if ($hasDateFilter && !$inRange($t['date'])) {
                return false;
            }
            if ($t['crop_field_id'] !== null && isset($allCrops[$t['crop_field_id']])) {
                $isArchived = $allCrops[$t['crop_field_id']]['is_archived'];
                if ($archived === 'active' && $isArchived) {
                    return false;
                }
                if ($archived === 'archived' && !$isArchived) {
                    return false;
                }
            }
            return true;
        }));
        usort($filteredTx, static fn ($a, $b) => strcmp((string) $b['date'], (string) $a['date']));

        $totalTransactionIncome = array_sum(array_map(static fn ($t) => $t['type'] === 'Income' ? $t['amount'] : 0.0, $filteredTx));

        $cashflowByMonth = [];
        foreach ($filteredTx as $t) {
            $m = substr($t['date'], 0, 7);
            $cashflowByMonth[$m] ??= ['month' => $m, 'income' => 0.0, 'expenses' => 0.0, 'net' => 0.0];
            if ($t['type'] === 'Income') {
                $cashflowByMonth[$m]['income'] += $t['amount'];
            } else {
                $cashflowByMonth[$m]['expenses'] += $t['amount'];
            }
            $cashflowByMonth[$m]['net'] = $cashflowByMonth[$m]['income'] - $cashflowByMonth[$m]['expenses'];
        }
        ksort($cashflowByMonth);
        $cashflowByMonth = array_values($cashflowByMonth);

        $incomeMap = [];
        foreach ($filteredTx as $t) {
            if ($t['type'] === 'Income') {
                $incomeMap[$t['category']] = ($incomeMap[$t['category']] ?? 0.0) + $t['amount'];
            }
        }
        $incomeByCategory = [];
        foreach ($incomeMap as $cat => $total) {
            $incomeByCategory[] = ['category' => $cat, 'total' => $total];
        }
        usort($incomeByCategory, static fn ($a, $b) => $b['total'] <=> $a['total']);

        $expenseMap = [
            'Inputs (seeds, fertiliser, chemicals)' => array_sum(array_column($cropRows, 'input_cost')),
            'Labour'                                => array_sum(array_column($cropRows, 'labour_cost')),
            'Other activity costs'                  => array_sum(array_column($cropRows, 'other_cost')),
            'Overhead (allocated)'                  => $displayedOverhead,
        ];
        $expensesByCategory = [];
        foreach ($expenseMap as $cat => $total) {
            if ($total > 0) {
                $expensesByCategory[] = ['category' => $cat, 'total' => $total];
            }
        }
        usort($expensesByCategory, static fn ($a, $b) => $b['total'] <=> $a['total']);

        // ---- yields ----
        $yieldRows = [];
        foreach ($displayCrops as $c) {
            foreach ($yieldsByCrop[$c['id']] ?? [] as $y) {
                if ($hasDateFilter && !$inRange($y['harvest_date'])) {
                    continue;
                }
                $yieldRows[] = [
                    'id'           => $y['id'],
                    'crop_name'    => $c['crop_name'],
                    'field_name'   => $c['field_name'],
                    'season'       => $c['season'],
                    'harvest_date' => $y['harvest_date'],
                    'display_qty'  => rtrim(rtrim(number_format($y['quantity'], 2), '0'), '.') . ' ' . $y['unit'],
                    'kg'           => $y['quantity_kg'],
                    'notes'        => $y['notes'],
                ];
            }
        }
        usort($yieldRows, static fn ($a, $b) => strcmp((string) $b['harvest_date'], (string) $a['harvest_date']));

        // ---- overhead per displayed crop ----
        $overheadPerCrop = array_values(array_filter($cropRows, static fn ($c) => $c['allocated_overhead'] > 0));
        usort($overheadPerCrop, static fn ($a, $b) => $b['allocated_overhead'] <=> $a['allocated_overhead']);

        // ---- analytics ----
        $cropProfitability = $cropRows;
        usort($cropProfitability, static fn ($a, $b) => $b['net_profit'] <=> $a['net_profit']);
        foreach ($cropProfitability as &$c) {
            $c['margin'] = $c['revenue'] > 0 ? ($c['net_profit'] / $c['revenue']) * 100 : 0.0;
        }
        unset($c);

        $fieldMap = [];
        foreach ($cropRows as $c) {
            $f = $c['field_name'];
            $fieldMap[$f] ??= ['field_name' => $f, 'area' => 0.0, 'revenue' => 0.0, 'cost' => 0.0, 'net_profit' => 0.0];
            $fieldMap[$f]['area'] += $c['area_planted'];
            $fieldMap[$f]['revenue'] += $c['revenue'];
            $fieldMap[$f]['cost'] += $c['total_cost'];
            $fieldMap[$f]['net_profit'] += $c['net_profit'];
        }
        $fieldProfitability = array_values($fieldMap);
        foreach ($fieldProfitability as &$f) {
            $f['profit_per_ha'] = $f['area'] > 0 ? $f['net_profit'] / $f['area'] : 0.0;
        }
        unset($f);
        usort($fieldProfitability, static fn ($a, $b) => $b['net_profit'] <=> $a['net_profit']);

        $inputEfficiency = [];
        foreach ($cropRows as $c) {
            $inputEfficiency[] = [
                'crop_name'      => $c['crop_name'],
                'variety'        => $c['variety'],
                'field_name'     => $c['field_name'],
                'season'         => $c['season'],
                'cost_per_ha'    => $c['area_planted'] > 0 ? $c['input_cost'] / $c['area_planted'] : 0.0,
                'cost_per_kg'    => $c['total_yield_kg'] > 0 ? $c['input_cost'] / $c['total_yield_kg'] : 0.0,
                'yield_response' => $c['input_cost'] > 0 ? $c['total_yield_kg'] / $c['input_cost'] : 0.0,
            ];
        }
        usort($inputEfficiency, static fn ($a, $b) => $b['yield_response'] <=> $a['yield_response']);

        $livestockProfitability = $this->loadLivestockProfitability($farmId);

        // ---- trends, derived from the same displayed crop rows ----
        $allSeasons = array_values(array_unique(array_column($cropRows, 'season')));
        sort($allSeasons);
        $allCropTypes = array_values(array_unique(array_column($cropRows, 'crop_name')));
        sort($allCropTypes);

        $yieldTrendMap = [];
        $costTrendMap = [];
        foreach ($cropRows as $c) {
            $cn = $c['crop_name'];
            $s = $c['season'];
            $yieldTrendMap[$cn][$s] ??= ['total_kg' => 0.0, 'total_area' => 0.0, 'yield_per_ha' => 0.0];
            $yieldTrendMap[$cn][$s]['total_kg'] += $c['total_yield_kg'];
            $yieldTrendMap[$cn][$s]['total_area'] += $c['area_planted'];
            $yieldTrendMap[$cn][$s]['yield_per_ha'] = $yieldTrendMap[$cn][$s]['total_area'] > 0
                ? $yieldTrendMap[$cn][$s]['total_kg'] / $yieldTrendMap[$cn][$s]['total_area'] : 0.0;

            $costTrendMap[$cn][$s] ??= ['total_cost' => 0.0, 'total_area' => 0.0, 'cost_per_ha' => 0.0];
            $costTrendMap[$cn][$s]['total_cost'] += $c['total_cost'];
            $costTrendMap[$cn][$s]['total_area'] += $c['area_planted'];
            $costTrendMap[$cn][$s]['cost_per_ha'] = $costTrendMap[$cn][$s]['total_area'] > 0
                ? $costTrendMap[$cn][$s]['total_cost'] / $costTrendMap[$cn][$s]['total_area'] : 0.0;
        }

        $yieldTrend = [];
        foreach ($allSeasons as $s) {
            $row = ['season' => $s];
            foreach ($allCropTypes as $cn) {
                $row[$cn] = $yieldTrendMap[$cn][$s]['yield_per_ha'] ?? 0.0;
            }
            $yieldTrend[] = $row;
        }
        $costPerHaTrend = [];
        foreach ($allSeasons as $s) {
            $row = ['season' => $s];
            foreach ($allCropTypes as $cn) {
                $row[$cn] = $costTrendMap[$cn][$s]['cost_per_ha'] ?? 0.0;
            }
            $costPerHaTrend[] = $row;
        }
        $costPerKgTrend = [];
        foreach ($allSeasons as $s) {
            $row = ['season' => $s];
            foreach ($allCropTypes as $cn) {
                $kg = $yieldTrendMap[$cn][$s]['total_kg'] ?? 0.0;
                $cost = $costTrendMap[$cn][$s]['total_cost'] ?? 0.0;
                $row[$cn] = $kg > 0 ? $cost / $kg : 0.0;
            }
            $costPerKgTrend[] = $row;
        }

        $revenueVsExpenses = [];
        foreach ($allSeasons as $s) {
            $rows = array_values(array_filter($cropRows, static fn ($c) => $c['season'] === $s));
            $revenueVsExpenses[] = [
                'season'      => $s,
                'revenue'     => array_sum(array_column($rows, 'revenue')),
                'expenses'    => array_sum(array_column($rows, 'total_cost')),
                'input_cost'  => array_sum(array_column($rows, 'input_cost')),
                'labour_cost' => array_sum(array_column($rows, 'labour_cost')),
                'other_cost'  => array_sum(array_column($rows, 'other_cost')),
                'overhead'    => array_sum(array_column($rows, 'allocated_overhead')),
            ];
        }

        $cropPerformance = [];
        foreach ($allCropTypes as $cn) {
            $seasonsForCrop = [];
            foreach (($yieldTrendMap[$cn] ?? []) as $s => $v) {
                if ($v['total_kg'] <= 0) {
                    continue;
                }
                $cost = $costTrendMap[$cn][$s] ?? ['cost_per_ha' => 0.0, 'total_cost' => 0.0];
                $seasonsForCrop[] = [
                    'season'      => $s,
                    'yield_per_ha' => $v['yield_per_ha'],
                    'cost_per_ha'  => $cost['cost_per_ha'],
                    'cost_per_kg'  => $v['total_kg'] > 0 ? $cost['total_cost'] / $v['total_kg'] : 0.0,
                ];
            }
            if ($seasonsForCrop === []) {
                continue;
            }
            usort($seasonsForCrop, static fn ($a, $b) => strcmp((string) $a['season'], (string) $b['season']));
            $count = count($seasonsForCrop);
            $avgYieldPerHa = array_sum(array_column($seasonsForCrop, 'yield_per_ha')) / $count;
            $avgCostPerHa = array_sum(array_column($seasonsForCrop, 'cost_per_ha')) / $count;
            $avgCostPerKg = array_sum(array_column($seasonsForCrop, 'cost_per_kg')) / $count;
            $byYield = $seasonsForCrop;
            usort($byYield, static fn ($a, $b) => $b['yield_per_ha'] <=> $a['yield_per_ha']);
            $best = $byYield[0];
            $worst = $byYield[count($byYield) - 1];
            $trend = 'stable';
            if ($count >= 2) {
                $first = $seasonsForCrop[0]['yield_per_ha'];
                $last = $seasonsForCrop[$count - 1]['yield_per_ha'];
                $delta = (($last - $first) / ($first ?: 1)) * 100;
                if ($delta > 5) {
                    $trend = 'improving';
                } elseif ($delta < -5) {
                    $trend = 'declining';
                }
            }
            $cropPerformance[] = [
                'crop_name'      => $cn,
                'seasons'        => $seasonsForCrop,
                'avg_yield_per_ha' => $avgYieldPerHa,
                'avg_cost_per_ha'  => $avgCostPerHa,
                'avg_cost_per_kg'  => $avgCostPerKg,
                'best_season'    => $best,
                'worst_season'   => $worst,
                'trend'          => $trend,
                'total_seasons'  => $count,
            ];
        }

        $breakEven = $cropRows;
        usort($breakEven, static fn ($a, $b) => strcmp((string) $b['season'], (string) $a['season']));
        $breakEven = array_map(static function ($c) {
            $c['break_even_price_per_kg'] = $c['total_yield_kg'] > 0 ? $c['total_cost'] / $c['total_yield_kg'] : null;
            $c['is_profitable'] = $c['net_profit'] > 0;
            $c['profit_margin_pct'] = $c['revenue'] > 0 ? ($c['net_profit'] / $c['revenue']) * 100 : null;
            return $c;
        }, $breakEven);

        $seasonSummaries = [];
        foreach ($allSeasons as $s) {
            $rows = array_values(array_filter($cropRows, static fn ($c) => $c['season'] === $s));
            $area = array_sum(array_column($rows, 'area_planted'));
            $totalKg = array_sum(array_column($rows, 'total_yield_kg'));
            $totalCostForSeason = array_sum(array_column($rows, 'total_cost'));
            $seasonSummaries[$s] = [
                'season'         => $s,
                'crop_count'     => count($rows),
                'area'           => $area,
                'total_cost'     => $totalCostForSeason,
                'revenue'        => array_sum(array_column($rows, 'revenue')),
                'net_profit'     => array_sum(array_column($rows, 'net_profit')),
                'total_yield_kg' => $totalKg,
                'yield_per_ha'   => $area > 0 ? $totalKg / $area : 0.0,
                'cost_per_ha'    => $area > 0 ? $totalCostForSeason / $area : 0.0,
                'crop_types'     => array_values(array_unique(array_column($rows, 'crop_name'))),
            ];
        }

        return [
            'all_seasons'    => $allSeasons,
            'all_crop_types' => $allCropTypes,
            'summary'        => [
                'total_crops'          => count($cropRows),
                'total_area'           => $totalArea,
                'total_kg_harvested'   => $totalKgHarvested,
                'avg_yield_per_ha'     => $avgYieldPerHa,
                'total_revenue'        => $totalRevenue,
                'total_expenses'       => $totalExpenses,
                'allocated_overhead'   => $displayedOverhead,
                'total_overhead'       => $totalOverhead,
                'unallocated_overhead' => $totalUnallocated,
            ],
            'crops'              => $cropRows,
            'by_season_breakdown' => $bySeasonBreakdown,
            'finance'            => [
                'income_by_category'   => $incomeByCategory,
                'expenses_by_category' => $expensesByCategory,
                'total_income'         => $totalTransactionIncome,
                'total_expenses'       => $totalExpenses,
                'transactions'         => array_slice($filteredTx, 0, 100),
                'transaction_count'    => count($filteredTx),
                'cashflow_by_month'    => $cashflowByMonth,
            ],
            'yields'   => $yieldRows,
            'overhead' => [
                'total_overhead'   => $totalOverhead,
                'total_allocated'  => $totalAllocated,
                'total_unallocated' => $totalUnallocated,
                'per_crop'         => $overheadPerCrop,
            ],
            'analytics' => [
                'crop_profitability'      => $cropProfitability,
                'field_profitability'     => $fieldProfitability,
                'input_efficiency'        => $inputEfficiency,
                'livestock_profitability' => $livestockProfitability,
            ],
            'trends' => [
                'yield_trend'         => $yieldTrend,
                'cost_per_ha_trend'   => $costPerHaTrend,
                'cost_per_kg_trend'   => $costPerKgTrend,
                'revenue_vs_expenses' => $revenueVsExpenses,
                'crop_performance'    => $cropPerformance,
                'break_even'          => $breakEven,
                'season_summaries'    => $seasonSummaries,
            ],
        ];
    }

    /** @return array<string,array<string,mixed>> keyed by crop_field id */
    private function loadAllCrops(string $farmId): array
    {
        $rows = $this->db()->select(
            'SELECT cf.id, cf.field_id, f.name AS field_name, ct.name AS crop_name, cf.variety,
                    cf.area_planted, cf.season, cf.planting_date, cf.expected_harvest_date, cf.is_archived
             FROM crop_fields cf
             JOIN fields f ON f.id = cf.field_id
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             WHERE cf.farm_id = :fid',
            ['fid' => $farmId],
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['id']] = [
                'id'                    => (string) $r['id'],
                'field_id'              => (string) $r['field_id'],
                'field_name'            => (string) $r['field_name'],
                'crop_name'             => (string) $r['crop_name'],
                'variety'               => (string) $r['variety'],
                'area_planted'          => (float) $r['area_planted'],
                'season'                => (string) $r['season'],
                'planting_date'         => (string) $r['planting_date'],
                'expected_harvest_date' => (string) $r['expected_harvest_date'],
                'is_archived'           => (int) $r['is_archived'] === 1,
            ];
        }
        return $out;
    }

    /** @return list<array<string,mixed>> */
    private function loadActivityCosts(string $farmId): array
    {
        $rows = $this->db()->select(
            "SELECT a.id, a.crop_field_id, a.date,
                    COALESCE((SELECT SUM(total_cost) FROM activity_inputs WHERE activity_id = a.id), 0) AS input_cost,
                    COALESCE((SELECT SUM(total_cost) FROM activity_labour WHERE activity_id = a.id), 0) AS labour_cost,
                    COALESCE((SELECT SUM(amount) FROM activity_other_costs WHERE activity_id = a.id), 0) AS other_cost
             FROM farm_activities a
             WHERE a.farm_id = :fid AND a.crop_field_id IS NOT NULL",
            ['fid' => $farmId],
        );
        foreach ($rows as &$r) {
            $r['crop_field_id'] = (string) $r['crop_field_id'];
            $r['date'] = (string) $r['date'];
            $r['input_cost'] = (float) $r['input_cost'];
            $r['labour_cost'] = (float) $r['labour_cost'];
            $r['other_cost'] = (float) $r['other_cost'];
        }
        unset($r);
        return $rows;
    }

    /** @return list<array<string,mixed>> */
    private function loadYields(string $farmId): array
    {
        $rows = $this->db()->select(
            'SELECT id, crop_field_id, harvest_date, quantity, unit, quantity_kg, notes
             FROM harvest_yields WHERE farm_id = :fid',
            ['fid' => $farmId],
        );
        foreach ($rows as &$r) {
            $r['crop_field_id'] = (string) $r['crop_field_id'];
            $r['harvest_date'] = (string) $r['harvest_date'];
            $r['quantity'] = (float) $r['quantity'];
            $r['unit'] = (string) $r['unit'];
            $r['quantity_kg'] = (float) $r['quantity_kg'];
            $r['notes'] = $r['notes'] !== null ? (string) $r['notes'] : null;
        }
        unset($r);
        return $rows;
    }

    /** @return list<array<string,mixed>> */
    private function loadTransactions(string $farmId): array
    {
        $rows = $this->db()->select(
            'SELECT id, type, category, amount, date, description, season, field_id, crop_field_id
             FROM transactions WHERE farm_id = :fid',
            ['fid' => $farmId],
        );
        foreach ($rows as &$r) {
            $r['amount'] = (float) $r['amount'];
            $r['date'] = (string) $r['date'];
            $r['season'] = $r['season'] !== null ? (string) $r['season'] : null;
            $r['field_id'] = $r['field_id'] !== null ? (string) $r['field_id'] : null;
            $r['crop_field_id'] = $r['crop_field_id'] !== null ? (string) $r['crop_field_id'] : null;
        }
        unset($r);
        return $rows;
    }

    /** @return list<array<string,mixed>> */
    private function loadOverheads(string $farmId, ?string $from, ?string $to): array
    {
        $sql = 'SELECT id, description, category, amount, date FROM overhead_expenses WHERE farm_id = :fid';
        $params = ['fid' => $farmId];
        if ($from !== null) {
            $sql .= ' AND date >= :from';
            $params['from'] = $from;
        }
        if ($to !== null) {
            $sql .= ' AND date <= :to';
            $params['to'] = $to;
        }
        $sql .= ' ORDER BY date ASC';
        $rows = $this->db()->select($sql, $params);
        foreach ($rows as &$r) {
            $r['amount'] = (float) $r['amount'];
            $r['date'] = (string) $r['date'];
        }
        unset($r);
        return $rows;
    }

    /** @return list<array{type:string,count:int,sales:float,production_value:float,expenses:float,health_cost:float,net_profit:float}> */
    private function loadLivestockProfitability(string $farmId): array
    {
        $rows = $this->db()->select(
            "SELECT lt.name AS type, a.acquisition_cost,
                    COALESCE((SELECT SUM(total_amount) FROM animal_sales WHERE animal_id = a.id), 0) AS sales,
                    COALESCE((SELECT SUM(total_value) FROM animal_production WHERE animal_id = a.id), 0) AS production_value,
                    COALESCE((SELECT SUM(amount) FROM animal_expenses WHERE animal_id = a.id), 0) AS expenses,
                    COALESCE((SELECT SUM(cost) FROM animal_health WHERE animal_id = a.id), 0) AS health_cost
             FROM animals a
             JOIN livestock_types lt ON lt.id = a.livestock_type_id
             WHERE a.farm_id = :fid",
            ['fid' => $farmId],
        );

        $map = [];
        foreach ($rows as $r) {
            $type = (string) $r['type'];
            $map[$type] ??= ['type' => $type, 'count' => 0, 'sales' => 0.0, 'production_value' => 0.0, 'expenses' => 0.0, 'health_cost' => 0.0, 'net_profit' => 0.0];
            $sales = (float) $r['sales'];
            $prod = (float) $r['production_value'];
            $exp = (float) $r['expenses'];
            $health = (float) $r['health_cost'];
            $acq = (float) ($r['acquisition_cost'] ?? 0.0);
            $map[$type]['count']++;
            $map[$type]['sales'] += $sales;
            $map[$type]['production_value'] += $prod;
            $map[$type]['expenses'] += $exp;
            $map[$type]['health_cost'] += $health;
            $map[$type]['net_profit'] += $sales + $prod - $exp - $health - $acq;
        }
        $out = array_values($map);
        usort($out, static fn ($a, $b) => $b['net_profit'] <=> $a['net_profit']);
        return $out;
    }
}
