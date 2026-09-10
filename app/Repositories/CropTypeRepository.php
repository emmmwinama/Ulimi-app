<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * Crop types = global canonical rows (farm_id NULL) + per-farm custom rows.
 */
final class CropTypeRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** Canonical + this farm's custom types, de-duplicated by lowercase name. @return array<int,array<string,mixed>> */
    public function available(string $farmId): array
    {
        $rows = $this->db->select(
            'SELECT * FROM crop_types
             WHERE farm_id IS NULL OR farm_id = :fid
             ORDER BY is_custom ASC, name ASC',
            ['fid' => $farmId],
        );

        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row['name']));
            if (isset($seen[$key])) {
                continue; // canonical wins (listed first)
            }
            $seen[$key] = true;
            $out[] = $row;
        }
        return $out;
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM crop_types WHERE id = :id AND (farm_id IS NULL OR farm_id = :fid) LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    /**
     * Return the id of an existing type matching the name (canonical or this
     * farm's), creating a farm-custom type if none exists.
     */
    public function resolveOrCreate(string $farmId, string $name): string
    {
        $name = trim($name);
        $existing = $this->db->selectOne(
            'SELECT id FROM crop_types
             WHERE LOWER(name) = LOWER(:name) AND (farm_id IS NULL OR farm_id = :fid)
             ORDER BY farm_id IS NOT NULL
             LIMIT 1',
            ['name' => $name, 'fid' => $farmId],
        );
        if ($existing !== null) {
            return (string) $existing['id'];
        }

        $id = Ulid::generate();
        $this->db->insert('crop_types', [
            'id'         => $id,
            'farm_id'    => $farmId,
            'name'       => $name,
            'is_custom'  => 1,
            'created_at' => Dates::nowUtc(),
        ]);
        return $id;
    }
}
