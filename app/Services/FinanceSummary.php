<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Read-only financial roll-ups for a farm. "Cost" is the sum of expense
 * transactions, activity costs (labour + inputs + other) and overhead
 * expenses. Everything is optionally filtered to one season.
 */
final class FinanceSummary
{
    private function db(): Database
    {
        return Database::instance();
    }

    /**
     * @return array{
     *   income:float, expense_tx:float, activity_cost:float, overhead:float,
     *   total_cost:float, net:float,
     *   by_category:array<string,float>
     * }
     */
    public function totals(string $farmId, ?string $season = null): array
    {
        $db = $this->db();
        $seasonWhere = $season !== null && $season !== '' ? ' AND season = :season' : '';
        $seasonBind  = $season !== null && $season !== '' ? ['season' => $season] : [];

        $income = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE farm_id = :fid AND type = 'Income'{$seasonWhere}",
            ['fid' => $farmId] + $seasonBind,
        );
        $expenseTx = (float) $db->scalar(
            "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE farm_id = :fid AND type = 'Expense'{$seasonWhere}",
            ['fid' => $farmId] + $seasonBind,
        );

        // Activity costs. Season is on the crop planting, so join through it when filtering.
        if ($season !== null && $season !== '') {
            $activityCost = (float) $db->scalar(
                "SELECT
                    COALESCE((SELECT SUM(al.total_cost) FROM activity_labour al
                        JOIN farm_activities a ON a.id = al.activity_id
                        JOIN crop_fields cf ON cf.id = a.crop_field_id
                        WHERE a.farm_id = :fid AND cf.season = :s1), 0)
                  + COALESCE((SELECT SUM(ai.total_cost) FROM activity_inputs ai
                        JOIN farm_activities a ON a.id = ai.activity_id
                        JOIN crop_fields cf ON cf.id = a.crop_field_id
                        WHERE a.farm_id = :fid2 AND cf.season = :s2), 0)
                  + COALESCE((SELECT SUM(ao.amount) FROM activity_other_costs ao
                        JOIN farm_activities a ON a.id = ao.activity_id
                        JOIN crop_fields cf ON cf.id = a.crop_field_id
                        WHERE a.farm_id = :fid3 AND cf.season = :s3), 0)",
                ['fid' => $farmId, 'fid2' => $farmId, 'fid3' => $farmId, 's1' => $season, 's2' => $season, 's3' => $season],
            );
        } else {
            $activityCost = (float) $db->scalar(
                "SELECT
                    COALESCE((SELECT SUM(total_cost) FROM activity_labour WHERE farm_id = :fid), 0)
                  + COALESCE((SELECT SUM(total_cost) FROM activity_inputs WHERE farm_id = :fid2), 0)
                  + COALESCE((SELECT SUM(amount) FROM activity_other_costs WHERE farm_id = :fid3), 0)",
                ['fid' => $farmId, 'fid2' => $farmId, 'fid3' => $farmId],
            );
        }

        // Overheads are farm-wide (no season) — only counted in the all-seasons view.
        $overhead = ($season === null || $season === '')
            ? (float) $db->scalar('SELECT COALESCE(SUM(amount),0) FROM overhead_expenses WHERE farm_id = :fid', ['fid' => $farmId])
            : 0.0;

        $catRows = $db->select(
            "SELECT category, SUM(amount) AS total FROM transactions
             WHERE farm_id = :fid AND type = 'Expense'{$seasonWhere}
             GROUP BY category ORDER BY total DESC",
            ['fid' => $farmId] + $seasonBind,
        );
        $byCategory = [];
        foreach ($catRows as $r) {
            $byCategory[(string) $r['category']] = (float) $r['total'];
        }

        $totalCost = $expenseTx + $activityCost + $overhead;

        return [
            'income'        => $income,
            'expense_tx'    => $expenseTx,
            'activity_cost' => $activityCost,
            'overhead'      => $overhead,
            'total_cost'    => $totalCost,
            'net'           => $income - $totalCost,
            'by_category'   => $byCategory,
        ];
    }

    /**
     * Monthly income vs cost for the trailing 12 months. Overheads + activity
     * costs are bucketed by their own date.
     *
     * @return list<array{month:string,income:float,cost:float}>
     */
    public function monthlyTrend(string $farmId): array
    {
        $db = $this->db();
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[date('Y-m', strtotime("-{$i} months"))] = ['income' => 0.0, 'cost' => 0.0];
        }
        $start = date('Y-m-01', strtotime('-11 months'));

        foreach ($db->select(
            "SELECT DATE_FORMAT(date, '%Y-%m') m, type, SUM(amount) s
             FROM transactions WHERE farm_id = :fid AND date >= :start GROUP BY m, type",
            ['fid' => $farmId, 'start' => $start],
        ) as $r) {
            $m = (string) $r['m'];
            if (!isset($months[$m])) {
                continue;
            }
            if ($r['type'] === 'Income') {
                $months[$m]['income'] += (float) $r['s'];
            } else {
                $months[$m]['cost'] += (float) $r['s'];
            }
        }

        foreach ($db->select(
            "SELECT DATE_FORMAT(date, '%Y-%m') m, SUM(amount) s
             FROM overhead_expenses WHERE farm_id = :fid AND date >= :start GROUP BY m",
            ['fid' => $farmId, 'start' => $start],
        ) as $r) {
            $m = (string) $r['m'];
            if (isset($months[$m])) {
                $months[$m]['cost'] += (float) $r['s'];
            }
        }

        // Aggregate each child table separately to avoid a join fan-out.
        $activityCostSql =
            "SELECT DATE_FORMAT(a.date, '%%Y-%%m') m, SUM(x.amt) s
             FROM farm_activities a
             JOIN (%s) x ON x.activity_id = a.id
             WHERE a.farm_id = :fid AND a.date >= :start
             GROUP BY m";
        foreach ([
            'SELECT activity_id, SUM(total_cost) amt FROM activity_labour GROUP BY activity_id',
            'SELECT activity_id, SUM(total_cost) amt FROM activity_inputs GROUP BY activity_id',
            'SELECT activity_id, SUM(amount) amt FROM activity_other_costs GROUP BY activity_id',
        ] as $inner) {
            foreach ($db->select(sprintf($activityCostSql, $inner), ['fid' => $farmId, 'start' => $start]) as $r) {
                $m = (string) $r['m'];
                if (isset($months[$m])) {
                    $months[$m]['cost'] += (float) $r['s'];
                }
            }
        }

        $out = [];
        foreach ($months as $m => $v) {
            $out[] = ['month' => (string) $m, 'income' => $v['income'], 'cost' => $v['cost']];
        }
        return $out;
    }

    public function totalYieldKg(string $farmId, ?string $season = null): float
    {
        $db = $this->db();
        if ($season !== null && $season !== '') {
            return (float) $db->scalar(
                'SELECT COALESCE(SUM(hy.quantity_kg),0) FROM harvest_yields hy
                 JOIN crop_fields cf ON cf.id = hy.crop_field_id
                 WHERE hy.farm_id = :fid AND cf.season = :s',
                ['fid' => $farmId, 's' => $season],
            );
        }
        return (float) $db->scalar(
            'SELECT COALESCE(SUM(quantity_kg),0) FROM harvest_yields WHERE farm_id = :fid',
            ['fid' => $farmId],
        );
    }

    public function cultivatableArea(string $farmId): float
    {
        return (float) $this->db()->scalar(
            'SELECT COALESCE(SUM(cultivatable_area),0) FROM fields WHERE farm_id = :fid',
            ['fid' => $farmId],
        );
    }
}
