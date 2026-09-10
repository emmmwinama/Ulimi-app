<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class TransactionRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * @param array{type?:string,category?:string,season?:string,crop_field_id?:string,from?:string,to?:string} $f
     * @return array<int,array<string,mixed>>
     */
    public function forFarm(string $farmId, array $f = []): array
    {
        $where = ['t.farm_id = :fid'];
        $bind = ['fid' => $farmId];
        foreach (['type', 'category', 'season'] as $k) {
            if (!empty($f[$k])) {
                $where[] = "t.$k = :$k";
                $bind[$k] = $f[$k];
            }
        }
        if (!empty($f['crop_field_id'])) {
            $where[] = 't.crop_field_id = :cfid';
            $bind['cfid'] = $f['crop_field_id'];
        }
        if (!empty($f['from'])) {
            $where[] = 't.date >= :from';
            $bind['from'] = $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 't.date <= :to';
            $bind['to'] = $f['to'];
        }

        return $this->db->select(
            'SELECT t.*, f.name AS field_name, ct.name AS crop_name
             FROM transactions t
             LEFT JOIN fields f ON f.id = t.field_id
             LEFT JOIN crop_fields cf ON cf.id = t.crop_field_id
             LEFT JOIN crop_types ct ON ct.id = cf.crop_type_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY t.date DESC, t.created_at DESC',
            $bind,
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM transactions WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countForFarm(string $farmId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM transactions WHERE farm_id = :fid', ['fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, string $userId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('transactions', [
            'id'                => $id,
            'farm_id'           => $farmId,
            'type'              => $data['type'],
            'category'          => $data['category'],
            'amount'            => $data['amount'],
            'date'              => $data['date'],
            'description'       => $data['description'],
            'season'            => $data['season'] ?? null,
            'field_id'          => $data['field_id'] ?? null,
            'crop_field_id'     => $data['crop_field_id'] ?? null,
            'harvest_yield_id'  => $data['harvest_yield_id'] ?? null,
            'inventory_item_id' => $data['inventory_item_id'] ?? null,
            'source'            => $data['source'] ?? 'manual',
            'created_by_id'     => $userId,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('transactions', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('transactions', ['id' => $id, 'farm_id' => $farmId]);
    }
}
