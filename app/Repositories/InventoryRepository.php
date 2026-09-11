<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class InventoryRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId, ?string $category = null): array
    {
        $sql = 'SELECT i.*,
                       (SELECT COALESCE(SUM(s.quantity_sold), 0) FROM inventory_sales s WHERE s.inventory_item_id = i.id) AS sold_qty,
                       (SELECT COALESCE(SUM(s.total_amount), 0) FROM inventory_sales s WHERE s.inventory_item_id = i.id) AS sold_revenue
                FROM inventory_items i WHERE i.farm_id = :fid';
        $bind = ['fid' => $farmId];
        if ($category !== null && $category !== '') {
            $sql .= ' AND i.category = :cat';
            $bind['cat'] = $category;
        }
        $sql .= ' ORDER BY i.category ASC, i.name ASC';
        return $this->db->select($sql, $bind);
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM inventory_items WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countForFarm(string $farmId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM inventory_items WHERE farm_id = :fid', ['fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('inventory_items', [
            'id'                    => $id,
            'farm_id'               => $farmId,
            'name'                  => $data['name'],
            'category'              => $data['category'],
            'unit'                  => $data['unit'],
            'quantity'              => $data['quantity'],
            'acquisition_unit_cost' => $data['acquisition_unit_cost'],
            'acquired_at'           => $data['acquired_at'],
            'unit_weight'           => $data['unit_weight'],
            'season'                => $data['season'],
            'crop_field_id'         => $data['crop_field_id'] ?? null,
            'harvest_yield_id'      => $data['harvest_yield_id'] ?? null,
            'notes'                 => $data['notes'],
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('inventory_items', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('inventory_items', ['id' => $id, 'farm_id' => $farmId]);
    }

    /** Atomic stock decrement guarded against overselling. Returns false if insufficient. */
    public function decrement(string $farmId, string $id, float $qty): bool
    {
        $affected = $this->db->run(
            'UPDATE inventory_items SET quantity = quantity - :q, updated_at = :now
             WHERE id = :id AND farm_id = :fid AND quantity >= :q2',
            ['q' => $qty, 'q2' => $qty, 'now' => Dates::nowUtc(), 'id' => $id, 'fid' => $farmId],
        )->rowCount();
        return $affected === 1;
    }

    /** @return array<int,array<string,mixed>> */
    public function salesForItem(string $farmId, string $itemId): array
    {
        return $this->db->select(
            'SELECT * FROM inventory_sales WHERE farm_id = :fid AND inventory_item_id = :id ORDER BY sale_date DESC',
            ['fid' => $farmId, 'id' => $itemId],
        );
    }

    /** @param array<string,mixed> $data */
    public function recordSale(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('inventory_sales', [
            'id'                => $id,
            'farm_id'           => $farmId,
            'inventory_item_id' => $data['inventory_item_id'],
            'transaction_id'    => $data['transaction_id'],
            'quantity_sold'     => $data['quantity_sold'],
            'unit'              => $data['unit'],
            'price_per_unit'    => $data['price_per_unit'],
            'total_amount'      => $data['total_amount'],
            'buyer_name'        => $data['buyer_name'],
            'sale_date'         => $data['sale_date'],
            'notes'             => $data['notes'],
            'created_at'        => Dates::nowUtc(),
        ]);
        return $id;
    }
}
