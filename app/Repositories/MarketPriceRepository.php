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
}
