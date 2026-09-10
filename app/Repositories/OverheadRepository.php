<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class OverheadRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId): array
    {
        return $this->db->select(
            'SELECT * FROM overhead_expenses WHERE farm_id = :fid ORDER BY date DESC, created_at DESC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM overhead_expenses WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function totalBetween(string $farmId, string $from, string $to): float
    {
        return (float) $this->db->scalar(
            'SELECT COALESCE(SUM(amount), 0) FROM overhead_expenses WHERE farm_id = :fid AND date BETWEEN :from AND :to',
            ['fid' => $farmId, 'from' => $from, 'to' => $to],
        );
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('overhead_expenses', [
            'id'          => $id,
            'farm_id'     => $farmId,
            'description' => $data['description'],
            'category'    => $data['category'],
            'amount'      => $data['amount'],
            'date'        => $data['date'],
            'recurring'   => $data['recurring'],
            'notes'       => $data['notes'],
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function update(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('overhead_expenses', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('overhead_expenses', ['id' => $id, 'farm_id' => $farmId]);
    }
}
