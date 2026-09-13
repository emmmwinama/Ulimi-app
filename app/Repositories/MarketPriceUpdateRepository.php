<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * Auto-fetched price proposals awaiting admin approval (App\Services\MarketPriceFeed).
 * A resolved row (approved or rejected) is deleted immediately rather than kept
 * around — the outcome already lives in `market_prices` plus the admin audit
 * log, so there's nothing this table needs to retain once it's actioned.
 */
final class MarketPriceUpdateRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function pending(): array
    {
        return $this->db->select(
            "SELECT * FROM market_price_updates WHERE status = 'pending' ORDER BY crop_name ASC, region ASC",
        );
    }

    public function countPending(): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM market_price_updates WHERE status = 'pending'");
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM market_price_updates WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function lastCheckedAt(): ?string
    {
        $row = $this->db->selectOne("SELECT last_checked_at FROM market_price_feed_state WHERE id = 'wfp'");
        return $row !== null && $row['last_checked_at'] !== null ? (string) $row['last_checked_at'] : null;
    }

    public function markChecked(): void
    {
        $this->db->update('market_price_feed_state', ['last_checked_at' => Dates::nowUtc()], ['id' => 'wfp']);
    }

    /**
     * Upserts one proposed row per (crop_name, region, source) — a repeat
     * fetch refreshes the still-pending proposal with new numbers rather
     * than piling up duplicates the admin has to sort through.
     *
     * @param array<string,mixed> $data crop_name, variety, unit, price_min,
     *   price_max, price_avg, market, region, currency, recorded_at, source,
     *   existing_price_id (nullable)
     */
    public function upsertPending(array $data): void
    {
        $now = Dates::nowUtc();
        $this->db->run(
            'INSERT INTO market_price_updates
                (id, existing_price_id, crop_name, variety, unit, price_min, price_max, price_avg,
                 market, region, currency, recorded_at, source, status, fetched_at)
             VALUES (:id, :existing_price_id, :crop_name, :variety, :unit, :price_min, :price_max, :price_avg,
                     :market, :region, :currency, :recorded_at, :source, \'pending\', :fetched_at)
             ON DUPLICATE KEY UPDATE
                existing_price_id = VALUES(existing_price_id),
                variety = VALUES(variety), unit = VALUES(unit),
                price_min = VALUES(price_min), price_max = VALUES(price_max), price_avg = VALUES(price_avg),
                market = VALUES(market), currency = VALUES(currency),
                recorded_at = VALUES(recorded_at), fetched_at = VALUES(fetched_at)',
            [
                'id' => Ulid::generate(),
                'existing_price_id' => $data['existing_price_id'] ?? null,
                'crop_name' => $data['crop_name'],
                'variety' => $data['variety'] ?? null,
                'unit' => $data['unit'],
                'price_min' => $data['price_min'],
                'price_max' => $data['price_max'],
                'price_avg' => $data['price_avg'],
                'market' => $data['market'],
                'region' => $data['region'],
                'currency' => $data['currency'],
                'recorded_at' => $data['recorded_at'],
                'source' => $data['source'],
                'fetched_at' => $now,
            ],
        );
    }

    public function delete(string $id): int
    {
        return $this->db->delete('market_price_updates', ['id' => $id]);
    }
}
