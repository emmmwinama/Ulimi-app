<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/** One optional storage/drying/loss record per harvest_yields row (1:1, upserted). */
final class ProduceStorageRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<string,mixed>|null */
    public function forHarvest(string $farmId, string $harvestYieldId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM produce_storage WHERE farm_id = :fid AND harvest_yield_id = :hid LIMIT 1',
            ['fid' => $farmId, 'hid' => $harvestYieldId],
        );
    }

    /**
     * Keyed by harvest_yield_id, for badging a list of harvests without an N+1 query.
     *
     * @param list<string> $harvestYieldIds
     * @return array<string,array<string,mixed>>
     */
    public function forHarvests(string $farmId, array $harvestYieldIds): array
    {
        $ids = array_values(array_unique(array_filter($harvestYieldIds, static fn ($v) => $v !== null && $v !== '')));
        if ($ids === []) {
            return [];
        }
        $placeholders = [];
        $bind = ['fid' => $farmId];
        foreach ($ids as $i => $id) {
            $key = 'id' . $i;
            $placeholders[] = ':' . $key;
            $bind[$key] = $id;
        }
        $rows = $this->db->select(
            'SELECT * FROM produce_storage WHERE farm_id = :fid AND harvest_yield_id IN (' . implode(',', $placeholders) . ')',
            $bind,
        );
        $byHarvest = [];
        foreach ($rows as $r) {
            $byHarvest[(string) $r['harvest_yield_id']] = $r;
        }
        return $byHarvest;
    }

    /** @param array<string,mixed> $data */
    public function upsert(string $farmId, string $harvestYieldId, array $data): void
    {
        $existing = $this->forHarvest($farmId, $harvestYieldId);
        $now = Dates::nowUtc();
        $payload = [
            'storage_location'  => $data['storage_location'],
            'drying_method'     => $data['drying_method'],
            'drying_date'       => $data['drying_date'],
            'quality_grade'     => $data['quality_grade'],
            'expected_loss_qty' => $data['expected_loss_qty'],
            'loss_reason'       => $data['loss_reason'],
            'notes'             => $data['notes'],
            'updated_at'        => $now,
        ];
        if ($existing === null) {
            $payload['id'] = Ulid::generate();
            $payload['farm_id'] = $farmId;
            $payload['harvest_yield_id'] = $harvestYieldId;
            $payload['created_at'] = $now;
            $this->db->insert('produce_storage', $payload);
            return;
        }
        $this->db->update('produce_storage', $payload, ['id' => $existing['id'], 'farm_id' => $farmId]);
    }
}
