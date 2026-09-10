<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class LivestockStats
{
    /**
     * @return array{
     *   total_head:int,
     *   by_type:list<array{name:string,icon:string,head:int}>,
     *   production_value:float,
     *   expenses:float,
     *   sales:float
     * }
     */
    public function forFarm(string $farmId): array
    {
        $db = Database::instance();

        $byType = $db->select(
            "SELECT lt.name, lt.icon,
                    (SELECT COUNT(*) FROM animals a WHERE a.livestock_type_id = lt.id AND a.status = 'Active') AS head
             FROM livestock_types lt WHERE lt.farm_id = :fid ORDER BY head DESC, lt.name ASC",
            ['fid' => $farmId],
        );

        return [
            'total_head'       => (int) $db->scalar("SELECT COUNT(*) FROM animals WHERE farm_id = :fid AND status = 'Active'", ['fid' => $farmId]),
            'by_type'          => array_map(static fn ($r) => [
                'name' => (string) $r['name'], 'icon' => (string) $r['icon'], 'head' => (int) $r['head'],
            ], $byType),
            'production_value' => (float) $db->scalar('SELECT COALESCE(SUM(total_value),0) FROM animal_production WHERE farm_id = :fid', ['fid' => $farmId]),
            'expenses'         => (float) $db->scalar('SELECT COALESCE(SUM(amount),0) FROM animal_expenses WHERE farm_id = :fid', ['fid' => $farmId]),
            'sales'            => (float) $db->scalar('SELECT COALESCE(SUM(total_amount),0) FROM animal_sales WHERE farm_id = :fid', ['fid' => $farmId]),
        ];
    }
}
