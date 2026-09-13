<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Data source for canned reports, record packs and the custom report builder.
 * Every section is a flat table (id-free of internals) built from a farm-scoped
 * query, optionally filtered by season / date range. Rendering (HTML for print,
 * or CSV) is the caller's job — this only assembles rows + column labels.
 */
final class ReportBuilder
{
    /**
     * section key => [label, columns(key=>label), sql, extra bind keys used]
     *
     * Every SQL uses :fid and optionally :season/:from/:to — callers only bind
     * what the query actually references, via named placeholders that simply
     * won't appear (and therefore won't be sent) when unused.
     */
    private const SECTIONS = [
        'fields' => [
            'label' => 'Fields',
            'columns' => ['name' => 'Field', 'soil_type' => 'Soil', 'total_area' => 'Total (ha)', 'cultivatable_area' => 'Cultivatable (ha)'],
            'sql' => 'SELECT name, soil_type, total_area, cultivatable_area FROM fields WHERE farm_id = :fid ORDER BY name',
        ],
        'crops' => [
            'label' => 'Crop plantings',
            'columns' => ['crop' => 'Crop', 'variety' => 'Variety', 'field' => 'Field', 'season' => 'Season', 'area_planted' => 'Area (ha)', 'planting_date' => 'Planted', 'expected_harvest_date' => 'Exp. harvest', 'status' => 'Status'],
            'sql' => "SELECT ct.name AS crop, cf.variety, f.name AS field, cf.season, cf.area_planted, cf.planting_date, cf.expected_harvest_date, cf.status
                       FROM crop_fields cf JOIN crop_types ct ON ct.id=cf.crop_type_id JOIN fields f ON f.id=cf.field_id
                       WHERE cf.farm_id = :fid {season_cf} ORDER BY cf.planting_date DESC",
        ],
        'activities' => [
            'label' => 'Activities',
            'columns' => ['date' => 'Date', 'activity_type' => 'Type', 'field' => 'Field', 'crop' => 'Crop', 'notes' => 'Notes'],
            'sql' => "SELECT a.date, a.activity_type, f.name AS field, ct.name AS crop, a.notes
                       FROM farm_activities a JOIN fields f ON f.id=a.field_id
                       LEFT JOIN crop_fields cf ON cf.id=a.crop_field_id LEFT JOIN crop_types ct ON ct.id=cf.crop_type_id
                       WHERE a.farm_id = :fid {date_a} ORDER BY a.date DESC",
        ],
        'yields' => [
            'label' => 'Harvest yields',
            'columns' => ['harvest_date' => 'Date', 'crop' => 'Crop', 'field' => 'Field', 'quantity' => 'Quantity', 'unit' => 'Unit', 'quantity_kg' => 'kg'],
            'sql' => "SELECT hy.harvest_date, ct.name AS crop, f.name AS field, hy.quantity, hy.unit, hy.quantity_kg
                       FROM harvest_yields hy JOIN crop_fields cf ON cf.id=hy.crop_field_id
                       JOIN crop_types ct ON ct.id=cf.crop_type_id JOIN fields f ON f.id=cf.field_id
                       WHERE hy.farm_id = :fid {date_hy} ORDER BY hy.harvest_date DESC",
        ],
        'finance' => [
            'label' => 'Finance transactions',
            'columns' => ['date' => 'Date', 'type' => 'Type', 'category' => 'Category', 'description' => 'Description', 'amount' => 'Amount'],
            'sql' => "SELECT date, type, category, description, amount FROM transactions WHERE farm_id = :fid {date_t} {season_t} ORDER BY date DESC",
        ],
        'pnl' => [
            'label' => 'Income & expense summary',
            'columns' => ['type' => 'Type', 'category' => 'Category', 'total' => 'Total'],
            'sql' => "SELECT type, category, SUM(amount) AS total FROM transactions WHERE farm_id = :fid {date_t} {season_t} GROUP BY type, category ORDER BY type ASC, total DESC",
        ],
        'climate_events' => [
            'label' => 'Climate events',
            'columns' => ['event_type' => 'Type', 'start_date' => 'Start', 'end_date' => 'End', 'description' => 'Description', 'estimated_loss_amount' => 'Est. loss'],
            'sql' => "SELECT event_type, start_date, end_date, description, estimated_loss_amount FROM climate_events WHERE farm_id = :fid {date_ce} ORDER BY start_date DESC",
        ],
        'overheads' => [
            'label' => 'Overheads',
            'columns' => ['date' => 'Date', 'description' => 'Description', 'category' => 'Category', 'amount' => 'Amount'],
            'sql' => "SELECT date, description, category, amount FROM overhead_expenses WHERE farm_id = :fid {date_o} ORDER BY date DESC",
        ],
        'inventory' => [
            'label' => 'Inventory',
            'columns' => ['name' => 'Item', 'category' => 'Category', 'quantity' => 'In stock', 'unit' => 'Unit'],
            'sql' => 'SELECT name, category, quantity, unit FROM inventory_items WHERE farm_id = :fid ORDER BY category, name',
        ],
        'employees' => [
            'label' => 'Employees',
            'columns' => ['name' => 'Name', 'role' => 'Role', 'pay_rate' => 'Pay rate', 'pay_rate_unit' => 'Per', 'is_active' => 'Active'],
            'sql' => 'SELECT name, role, pay_rate, pay_rate_unit, is_active FROM employees WHERE farm_id = :fid ORDER BY name',
        ],
        'livestock' => [
            'label' => 'Livestock',
            'columns' => ['tag' => 'Tag', 'name' => 'Name', 'type' => 'Type', 'sex' => 'Sex', 'status' => 'Status', 'weight' => 'Weight (kg)'],
            'sql' => "SELECT a.tag, a.name, lt.name AS type, a.sex, a.status, a.weight
                       FROM animals a JOIN livestock_types lt ON lt.id=a.livestock_type_id
                       WHERE a.farm_id = :fid ORDER BY lt.name, a.tag",
        ],
    ];

    /** @return array<string,array{label:string,columns:string[]}> section key => metadata, for building a picker UI */
    public static function availableSections(): array
    {
        $out = [];
        foreach (self::SECTIONS as $key => $s) {
            $out[$key] = ['label' => $s['label'], 'columns' => $s['columns']];
        }
        return $out;
    }

    /**
     * @param list<string> $sections
     * @param array{season?:string,from?:string,to?:string} $filters
     * @return array<string,array{label:string,columns:array<string,string>,rows:array<int,array<string,mixed>>}>
     */
    public function build(string $farmId, array $sections, array $filters = []): array
    {
        $db = Database::instance();
        $season = $filters['season'] ?? '';
        $from = $filters['from'] ?? '';
        $to = $filters['to'] ?? '';

        $out = [];
        foreach ($sections as $key) {
            if (!isset(self::SECTIONS[$key])) {
                continue;
            }
            $def = self::SECTIONS[$key];
            $sql = $def['sql'];
            $bind = ['fid' => $farmId];

            $sql = str_replace('{season_cf}', $season !== '' ? 'AND cf.season = :season' : '', $sql);
            $sql = str_replace('{season_t}', $season !== '' ? 'AND season = :season' : '', $sql);
            $sql = str_replace('{date_a}', $this->dateClause('a.date', $from, $to), $sql);
            $sql = str_replace('{date_hy}', $this->dateClause('hy.harvest_date', $from, $to), $sql);
            $sql = str_replace('{date_t}', $this->dateClause('date', $from, $to), $sql);
            $sql = str_replace('{date_o}', $this->dateClause('date', $from, $to), $sql);
            $sql = str_replace('{date_ce}', $this->dateClause('start_date', $from, $to), $sql);

            if ($season !== '' && str_contains($def['sql'], '{season_')) {
                $bind['season'] = $season;
            }
            if ($from !== '' && str_contains($def['sql'], '{date_')) {
                $bind['from'] = $from;
            }
            if ($to !== '' && str_contains($def['sql'], '{date_')) {
                $bind['to'] = $to;
            }

            $out[$key] = [
                'label'   => $def['label'],
                'columns' => $def['columns'],
                'rows'    => $db->select($sql, $bind),
            ];
        }
        return $out;
    }

    private function dateClause(string $col, string $from, string $to): string
    {
        $parts = [];
        if ($from !== '') {
            $parts[] = "{$col} >= :from";
        }
        if ($to !== '') {
            $parts[] = "{$col} <= :to";
        }
        return $parts === [] ? '' : 'AND ' . implode(' AND ', $parts);
    }

    /**
     * Render one section's rows as CSV text (with header row).
     *
     * @param array{label:string,columns:array<string,string>,rows:array<int,array<string,mixed>>} $section
     */
    public function toCsv(array $section): string
    {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, array_values($section['columns']));
        foreach ($section['rows'] as $row) {
            $line = [];
            foreach (array_keys($section['columns']) as $col) {
                $v = $row[$col] ?? '';
                $line[] = is_bool($v) ? ($v ? 'Yes' : 'No') : (string) $v;
            }
            fputcsv($fh, $line);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv === false ? '' : $csv;
    }
}
