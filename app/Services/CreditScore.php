<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * Farm credit-readiness score (0–100) derived entirely from the farm's own
 * records — no external bureau data. It measures how *fundable* the record set
 * looks to a lender, not creditworthiness in a regulated sense.
 *
 * Factors (weight):
 *   record_completeness (25) — are fields, crops, activities and finance all in use?
 *   cashflow            (25) — positive net over the last 12 months, and its size
 *   revenue_evidence    (20) — sales backed by yields / inventory, not bare entries
 *   activity_discipline (15) — activities logged across the season, with costs
 *   data_recency        (15) — how fresh the most recent records are
 */
final class CreditScore
{
    private const WEIGHTS = [
        'record_completeness' => 25,
        'cashflow'            => 25,
        'revenue_evidence'    => 20,
        'activity_discipline' => 15,
        'data_recency'        => 15,
    ];

    private function db(): Database
    {
        return Database::instance();
    }

    /**
     * @return array{
     *   score:int, grade:string,
     *   factors:list<array{key:string,label:string,weight:int,earned:int,detail:string}>
     * }
     */
    public function compute(string $farmId): array
    {
        $db = $this->db();
        $one = static fn (string $sql, array $b = []) => (float) $db->scalar($sql, $b + ['fid' => $farmId]);

        $fields      = $one('SELECT COUNT(*) FROM fields WHERE farm_id = :fid');
        $crops       = $one('SELECT COUNT(*) FROM crop_fields WHERE farm_id = :fid');
        $activities  = $one('SELECT COUNT(*) FROM farm_activities WHERE farm_id = :fid');
        $txCount     = $one('SELECT COUNT(*) FROM transactions WHERE farm_id = :fid');
        $yields      = $one('SELECT COUNT(*) FROM harvest_yields WHERE farm_id = :fid');

        $income12  = $one("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE farm_id = :fid AND type='Income'  AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)");
        $expense12 = $one("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE farm_id = :fid AND type='Expense' AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)");
        $activityCost = $one(
            "SELECT COALESCE((SELECT SUM(total_cost) FROM activity_labour WHERE farm_id=:fid),0)
                  + COALESCE((SELECT SUM(total_cost) FROM activity_inputs WHERE farm_id=:fid),0)
                  + COALESCE((SELECT SUM(amount)     FROM activity_other_costs WHERE farm_id=:fid),0)"
        );
        $overhead12 = $one("SELECT COALESCE(SUM(amount),0) FROM overhead_expenses WHERE farm_id = :fid AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)");
        $net12 = $income12 - $expense12 - $overhead12;

        $salesLinked = $one("SELECT COUNT(*) FROM transactions WHERE farm_id = :fid AND type='Income' AND (harvest_yield_id IS NOT NULL OR inventory_item_id IS NOT NULL OR source='inventory_sale')");
        $salesTotal  = $one("SELECT COUNT(*) FROM transactions WHERE farm_id = :fid AND type='Income'");

        $activitiesWithCost = $one(
            "SELECT COUNT(DISTINCT a.id) FROM farm_activities a
             WHERE a.farm_id = :fid AND (
                EXISTS(SELECT 1 FROM activity_labour l WHERE l.activity_id=a.id)
             OR EXISTS(SELECT 1 FROM activity_inputs i WHERE i.activity_id=a.id)
             OR EXISTS(SELECT 1 FROM activity_other_costs o WHERE o.activity_id=a.id))"
        );

        $lastActivity = (string) $db->scalar('SELECT COALESCE(MAX(date), "") FROM farm_activities WHERE farm_id = :fid', ['fid' => $farmId]);
        $lastTx = (string) $db->scalar('SELECT COALESCE(MAX(date), "") FROM transactions WHERE farm_id = :fid', ['fid' => $farmId]);
        $mostRecent = max($lastActivity, $lastTx);

        /* -- factor scoring -- */
        $factors = [];

        $completeParts = ($fields > 0 ? 1 : 0) + ($crops > 0 ? 1 : 0) + ($activities >= 3 ? 1 : 0) + ($txCount >= 3 ? 1 : 0);
        $factors[] = $this->factor('record_completeness', 'Record completeness',
            (int) round(self::WEIGHTS['record_completeness'] * $completeParts / 4),
            "{$fields} fields, {$crops} plantings, {$activities} activities, {$txCount} transactions");

        $cashflowScore = match (true) {
            $net12 <= 0                 => 0.15,
            $net12 < $expense12 * 0.15  => 0.55,
            $net12 < $expense12 * 0.4   => 0.8,
            default                     => 1.0,
        };
        $factors[] = $this->factor('cashflow', '12-month cashflow',
            (int) round(self::WEIGHTS['cashflow'] * $cashflowScore),
            'Net ' . number_format($net12) . ' over the last year');

        $evidenceRatio = $salesTotal > 0 ? $salesLinked / $salesTotal : 0.0;
        $factors[] = $this->factor('revenue_evidence', 'Revenue evidence',
            (int) round(self::WEIGHTS['revenue_evidence'] * ($salesTotal === 0.0 ? 0.2 : $evidenceRatio)),
            $salesTotal > 0 ? round($evidenceRatio * 100) . '% of sales linked to a yield or stock record' : 'No income recorded');

        $disciplineRatio = $activities > 0 ? $activitiesWithCost / $activities : 0.0;
        $factors[] = $this->factor('activity_discipline', 'Activity discipline',
            (int) round(self::WEIGHTS['activity_discipline'] * ($activities === 0.0 ? 0.0 : min(1.0, $disciplineRatio + ($activities >= 10 ? 0.1 : 0)))),
            $activities > 0 ? round($disciplineRatio * 100) . '% of activities carry a cost breakdown' : 'No activities logged');

        $daysOld = $mostRecent !== '' ? max(0, (int) floor((time() - strtotime($mostRecent . ' UTC')) / 86400)) : 999;
        $recencyScore = match (true) {
            $daysOld <= 30  => 1.0,
            $daysOld <= 90  => 0.7,
            $daysOld <= 180 => 0.4,
            $daysOld <= 365 => 0.2,
            default         => 0.0,
        };
        $factors[] = $this->factor('data_recency', 'Data recency',
            (int) round(self::WEIGHTS['data_recency'] * $recencyScore),
            $mostRecent !== '' ? "Most recent record {$daysOld} days ago" : 'No dated records');

        $score = array_sum(array_map(static fn ($f) => $f['earned'], $factors));
        $score = max(0, min(100, (int) $score));

        return ['score' => $score, 'grade' => self::grade($score), 'factors' => $factors];
    }

    public function persist(string $farmId, string $userId): array
    {
        $result = $this->compute($farmId);
        $this->db()->insert('farm_credit_scores', [
            'id'           => Ulid::generate(),
            'farm_id'      => $farmId,
            'user_id'      => $userId,
            'score'        => $result['score'],
            'grade'        => $result['grade'],
            'factors'      => json_encode($result['factors'], JSON_UNESCAPED_SLASHES),
            'generated_at' => Dates::nowUtc(),
        ]);
        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    public function history(string $farmId, int $limit = 12): array
    {
        return $this->db()->select(
            'SELECT score, grade, generated_at FROM farm_credit_scores WHERE farm_id = :fid ORDER BY generated_at DESC LIMIT ' . max(1, $limit),
            ['fid' => $farmId],
        );
    }

    /** @return array{key:string,label:string,weight:int,earned:int,detail:string} */
    private function factor(string $key, string $label, int $earned, string $detail): array
    {
        return [
            'key' => $key, 'label' => $label,
            'weight' => self::WEIGHTS[$key], 'earned' => max(0, min(self::WEIGHTS[$key], $earned)),
            'detail' => $detail,
        ];
    }

    private static function grade(int $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 70 => 'B',
            $score >= 55 => 'C',
            $score >= 40 => 'D',
            default      => 'E',
        };
    }
}
