<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Lot traceability + a buyer-ready checklist. A lot is one crop planting; its
 * lot ID encodes season, field and crop so a bag can be traced to a record.
 */
final class Traceability
{
    private function db(): Database
    {
        return Database::instance();
    }

    /** e.g. "2526-KAN-MZE-4F7A2K" */
    public static function lotId(string $season, string $fieldName, string $cropName, string $rowId): string
    {
        $seasonCode = preg_replace('/\D+/', '', $season) ?: '0000';
        $seasonCode = substr($seasonCode, 0, 4) ?: '0000';
        $fieldCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $fieldName) ?: 'FLD', 0, 3));
        $cropCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cropName) ?: 'CRP', 0, 3));
        $short = strtoupper(substr($rowId, -6));
        return "{$seasonCode}-{$fieldCode}-{$cropCode}-{$short}";
    }

    /**
     * One row per active planting with its evidence checklist.
     *
     * @return list<array{
     *   crop_field_id:string, lot_id:string, crop:string, field:string, season:string,
     *   checks:array<string,bool>, score:int
     * }>
     */
    public function lots(string $farmId): array
    {
        $db = $this->db();
        $plantings = $db->select(
            "SELECT cf.id, cf.season, cf.variety, ct.name AS crop_name, f.name AS field_name
             FROM crop_fields cf
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE cf.farm_id = :fid AND cf.is_archived = 0
             ORDER BY cf.planting_date DESC",
            ['fid' => $farmId],
        );

        $out = [];
        foreach ($plantings as $p) {
            $cfId = (string) $p['id'];
            $checks = [
                'Planting recorded'   => true,
                'Field activities'    => $db->scalar('SELECT 1 FROM farm_activities WHERE crop_field_id = :id LIMIT 1', ['id' => $cfId]) !== false,
                'Input records'       => $db->scalar('SELECT 1 FROM activity_inputs ai JOIN farm_activities a ON a.id = ai.activity_id WHERE a.crop_field_id = :id LIMIT 1', ['id' => $cfId]) !== false,
                'Spray / pest record' => $db->scalar("SELECT 1 FROM farm_activities WHERE crop_field_id = :id AND activity_type = 'Pest & Disease Control' LIMIT 1", ['id' => $cfId]) !== false,
                'Harvest yield'       => $db->scalar('SELECT 1 FROM harvest_yields WHERE crop_field_id = :id LIMIT 1', ['id' => $cfId]) !== false,
                'Sale linked'         => $db->scalar('SELECT 1 FROM transactions WHERE crop_field_id = :id AND type = \'Income\' LIMIT 1', ['id' => $cfId]) !== false,
            ];
            $passed = count(array_filter($checks));
            $out[] = [
                'crop_field_id' => $cfId,
                'lot_id'        => self::lotId((string) $p['season'], (string) $p['field_name'], (string) $p['crop_name'], $cfId),
                'crop'          => (string) $p['crop_name'] . ((string) $p['variety'] !== '' ? ' (' . $p['variety'] . ')' : ''),
                'field'         => (string) $p['field_name'],
                'season'        => (string) $p['season'],
                'checks'        => $checks,
                'score'         => (int) round($passed / count($checks) * 100),
            ];
        }
        return $out;
    }

    /**
     * Farm-wide compliance / evidence-gap view: broad record categories and
     * whether each is populated, with a count.
     *
     * @return list<array{area:string,present:bool,count:int,hint:string}>
     */
    public function complianceChecklist(string $farmId): array
    {
        $db = $this->db();
        // $t is always a literal at the call site below, never external input —
        // quoteIdent() is defence in depth, not the only guard.
        $c = static fn (string $t, string $extra = '') => (int) $db->scalar(
            "SELECT COUNT(*) FROM {$db->quoteIdent($t)} WHERE farm_id = :fid {$extra}",
            ['fid' => $farmId],
        );

        $rows = [
            ['Field register',        $c('fields'),              'Every parcel with area and soil type'],
            ['Crop plantings',        $c('crop_fields'),         'What was planted, where and when'],
            ['Field activities',      $c('farm_activities'),     'Land prep through harvest, dated'],
            ['Input records',         $c('activity_inputs'),     'Seed, fertiliser and chemical use'],
            ['Labour records',        $c('activity_labour'),     'Who did the work and the cost'],
            ['Harvest yields',        $c('harvest_yields'),      'Quantity harvested per planting'],
            ['Finance transactions',  $c('transactions'),        'Income and expenses with dates'],
            ['Overhead expenses',     $c('overhead_expenses'),   'Rent, salaries, utilities'],
            ['Inventory records',     $c('inventory_items'),     'Produce and input stock on hand'],
            ['Employee roster',       $c('employees'),           'Workers and pay rates'],
        ];

        return array_map(static fn ($r) => [
            'area' => $r[0], 'present' => $r[1] > 0, 'count' => $r[1], 'hint' => $r[2],
        ], $rows);
    }
}
