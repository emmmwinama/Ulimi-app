<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Reference market price data. Not farm-scoped — this is shared national/
 * regional reference data (maintained by admins in Phase 9); farms only read it.
 */
final class MarketPriceRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function active(?string $crop = null): array
    {
        $sql = 'SELECT * FROM market_prices WHERE is_active = 1';
        $bind = [];
        if ($crop !== null && $crop !== '') {
            $sql .= ' AND crop_name = :crop';
            $bind['crop'] = $crop;
        }
        $sql .= ' ORDER BY crop_name ASC, price_avg DESC';
        return $this->db->select($sql, $bind);
    }

    /** @return list<string> */
    public function crops(): array
    {
        $rows = $this->db->select('SELECT DISTINCT crop_name FROM market_prices WHERE is_active = 1 ORDER BY crop_name ASC');
        return array_map(static fn (array $r): string => (string) $r['crop_name'], $rows);
    }

    /* ------------------------------------------------------------- admin */

    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return $this->db->select('SELECT * FROM market_prices ORDER BY is_active DESC, crop_name ASC');
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM market_prices WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** Finds a previous auto-import for this crop/region so a re-fetch updates it in place. */
    public function findBySourceCropRegion(string $source, string $cropName, string $region): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM market_prices WHERE source = :source AND crop_name = :crop AND region = :region LIMIT 1',
            ['source' => $source, 'crop' => $cropName, 'region' => $region],
        );
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): string
    {
        $id = \App\Support\Ulid::generate();
        $data['id'] = $id;
        $this->db->insert('market_prices', $data);
        return $id;
    }

    /** @param array<string,mixed> $data */
    public function update(string $id, array $data): void
    {
        $this->db->update('market_prices', $data, ['id' => $id]);
    }

    public function delete(string $id): int
    {
        return $this->db->delete('market_prices', ['id' => $id]);
    }
}
