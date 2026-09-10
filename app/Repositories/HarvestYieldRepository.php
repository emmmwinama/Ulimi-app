<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class HarvestYieldRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId, ?string $cropFieldId = null): array
    {
        $sql = 'SELECT hy.*, ct.name AS crop_name, f.name AS field_name, cf.season, cf.variety
                FROM harvest_yields hy
                JOIN crop_fields cf ON cf.id = hy.crop_field_id
                JOIN crop_types ct ON ct.id = cf.crop_type_id
                JOIN fields f ON f.id = cf.field_id
                WHERE hy.farm_id = :fid';
        $bind = ['fid' => $farmId];
        if ($cropFieldId !== null) {
            $sql .= ' AND hy.crop_field_id = :cfid';
            $bind['cfid'] = $cropFieldId;
        }
        $sql .= ' ORDER BY hy.harvest_date DESC';
        return $this->db->select($sql, $bind);
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT hy.*, ct.name AS crop_name, f.name AS field_name
             FROM harvest_yields hy
             JOIN crop_fields cf ON cf.id = hy.crop_field_id
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE hy.id = :id AND hy.farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    /**
     * @param array{crop_field_id:string,harvest_date:string,quantity:float,unit:string,unit_weight:?float,notes:?string} $data
     */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('harvest_yields', [
            'id'            => $id,
            'farm_id'       => $farmId,
            'crop_field_id' => $data['crop_field_id'],
            'harvest_date'  => $data['harvest_date'],
            'quantity'      => $data['quantity'],
            'unit'          => $data['unit'],
            'unit_weight'   => $data['unit_weight'],
            'quantity_kg'   => self::toKg($data['quantity'], $data['unit'], $data['unit_weight']),
            'notes'         => $data['notes'],
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        return $id;
    }

    /** @param array{quantity:float,unit:string,unit_weight:?float,harvest_date:string,notes:?string} $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $this->db->update('harvest_yields', [
            'harvest_date' => $data['harvest_date'],
            'quantity'     => $data['quantity'],
            'unit'         => $data['unit'],
            'unit_weight'  => $data['unit_weight'],
            'quantity_kg'  => self::toKg($data['quantity'], $data['unit'], $data['unit_weight']),
            'notes'        => $data['notes'],
            'updated_at'   => Dates::nowUtc(),
        ], ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('harvest_yields', ['id' => $id, 'farm_id' => $farmId]);
    }

    public static function toKg(float $quantity, string $unit, ?float $unitWeight): float
    {
        return match (strtolower($unit)) {
            'kg'              => $quantity,
            'tonne', 'tonnes' => $quantity * 1000,
            'bag', 'bags'     => $quantity * ($unitWeight ?: 50),
            'crate', 'crates' => $quantity * ($unitWeight ?: 20),
            default           => $unitWeight ? $quantity * $unitWeight : $quantity,
        };
    }
}
