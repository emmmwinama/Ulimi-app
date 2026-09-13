<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Read-only rollups over member farms' EXISTING records — no new storage,
 * no data duplication. Every query is scoped to the exact set of member
 * farm ids resolved by the caller from CooperativeRepository::memberFarmIds(),
 * never a broader set.
 */
final class CooperativeStats
{
    /**
     * Combined stock-on-hand by category, across every member farm.
     *
     * @param list<string> $farmIds
     * @return array<int,array{category:string,item_count:int,total_quantity:float}>
     */
    public function inventoryRollup(array $farmIds): array
    {
        if ($farmIds === []) {
            return [];
        }
        return Database::instance()->select(
            'SELECT category, COUNT(*) AS item_count, COALESCE(SUM(quantity), 0) AS total_quantity
             FROM inventory_items WHERE farm_id IN (' . $this->placeholders($farmIds) . ')
             GROUP BY category ORDER BY total_quantity DESC',
            $this->bind($farmIds),
        );
    }

    /**
     * Combined harvest volume by crop, across every member farm, last 12 months.
     *
     * @param list<string> $farmIds
     * @return array<int,array{crop_name:string,total_kg:float,harvest_count:int}>
     */
    public function productionRollup(array $farmIds): array
    {
        if ($farmIds === []) {
            return [];
        }
        return Database::instance()->select(
            'SELECT ct.name AS crop_name, COUNT(*) AS harvest_count, COALESCE(SUM(hy.quantity_kg), 0) AS total_kg
             FROM harvest_yields hy
             JOIN crop_fields cf ON cf.id = hy.crop_field_id
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             WHERE hy.farm_id IN (' . $this->placeholders($farmIds) . ')
               AND hy.harvest_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY ct.name ORDER BY total_kg DESC',
            $this->bind($farmIds),
        );
    }

    /** @param list<string> $farmIds */
    private function placeholders(array $farmIds): string
    {
        return implode(',', array_map(static fn (int $i): string => ':fid' . $i, array_keys($farmIds)));
    }

    /** @param list<string> $farmIds @return array<string,string> */
    private function bind(array $farmIds): array
    {
        $bind = [];
        foreach (array_values($farmIds) as $i => $id) {
            $bind['fid' . $i] = $id;
        }
        return $bind;
    }
}
