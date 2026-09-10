<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * Livestock aggregate: types, animals and the five per-animal event logs.
 * Every method takes the farm id from the caller (FarmContext).
 */
final class LivestockRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /* ------------------------------------------------------------- types */

    /** @return array<int,array<string,mixed>> */
    public function types(string $farmId): array
    {
        return $this->db->select(
            'SELECT lt.*, (SELECT COUNT(*) FROM animals a WHERE a.livestock_type_id = lt.id AND a.status = \'Active\') AS head
             FROM livestock_types lt WHERE lt.farm_id = :fid ORDER BY lt.name ASC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function findType(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM livestock_types WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function createType(string $farmId, string $name, string $category, string $icon): string
    {
        $id = Ulid::generate();
        $this->db->insert('livestock_types', [
            'id' => $id, 'farm_id' => $farmId, 'name' => $name,
            'category' => $category, 'icon' => $icon, 'created_at' => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function deleteType(string $farmId, string $id): int
    {
        return $this->db->delete('livestock_types', ['id' => $id, 'farm_id' => $farmId]);
    }

    public function typeHasAnimals(string $farmId, string $id): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM animals WHERE livestock_type_id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        ) !== false;
    }

    /* ----------------------------------------------------------- animals */

    /**
     * @param array{type_id?:string,status?:string} $f
     * @return array<int,array<string,mixed>>
     */
    public function animals(string $farmId, array $f = []): array
    {
        $where = ['a.farm_id = :fid'];
        $bind = ['fid' => $farmId];
        if (!empty($f['type_id'])) {
            $where[] = 'a.livestock_type_id = :tid';
            $bind['tid'] = $f['type_id'];
        }
        if (!empty($f['status'])) {
            $where[] = 'a.status = :status';
            $bind['status'] = $f['status'];
        }
        return $this->db->select(
            'SELECT a.*, lt.name AS type_name, lt.icon AS type_icon
             FROM animals a JOIN livestock_types lt ON lt.id = a.livestock_type_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY a.status ASC, lt.name ASC, a.tag ASC, a.name ASC',
            $bind,
        );
    }

    /** @return array<string,mixed>|null */
    public function findAnimal(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT a.*, lt.name AS type_name, lt.icon AS type_icon, p.tag AS parent_tag, p.name AS parent_name
             FROM animals a
             JOIN livestock_types lt ON lt.id = a.livestock_type_id
             LEFT JOIN animals p ON p.id = a.parent_id
             WHERE a.id = :id AND a.farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countAnimals(string $farmId, bool $activeOnly = true): int
    {
        $sql = 'SELECT COUNT(*) FROM animals WHERE farm_id = :fid';
        if ($activeOnly) {
            $sql .= " AND status = 'Active'";
        }
        return (int) $this->db->scalar($sql, ['fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function createAnimal(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('animals', [
            'id' => $id, 'farm_id' => $farmId,
            'livestock_type_id' => $data['livestock_type_id'],
            'tag' => $data['tag'], 'name' => $data['name'], 'animal_group' => $data['animal_group'],
            'sex' => $data['sex'], 'birth_date' => $data['birth_date'],
            'acquisition_date' => $data['acquisition_date'], 'acquisition_type' => $data['acquisition_type'],
            'acquisition_cost' => $data['acquisition_cost'], 'status' => $data['status'],
            'breed' => $data['breed'], 'colour' => $data['colour'], 'weight' => $data['weight'],
            'notes' => $data['notes'], 'parent_id' => $data['parent_id'],
            'created_at' => $now, 'updated_at' => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $data */
    public function updateAnimal(string $farmId, string $id, array $data): void
    {
        $data['updated_at'] = Dates::nowUtc();
        $this->db->update('animals', $data, ['id' => $id, 'farm_id' => $farmId]);
    }

    public function deleteAnimal(string $farmId, string $id): int
    {
        return $this->db->delete('animals', ['id' => $id, 'farm_id' => $farmId]);
    }

    /* ------------------------------------------------------- event logs */

    /** @return array<int,array<string,mixed>> */
    public function events(string $table, string $farmId, string $animalId): array
    {
        $allowed = ['animal_health', 'animal_production', 'animal_weight', 'animal_expenses', 'animal_sales'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('bad table');
        }
        $dateCol = $table === 'animal_sales' ? 'sale_date' : 'date';
        return $this->db->select(
            "SELECT * FROM {$this->db->quoteIdent($table)}
             WHERE farm_id = :fid AND animal_id = :aid ORDER BY {$dateCol} DESC",
            ['fid' => $farmId, 'aid' => $animalId],
        );
    }

    /**
     * @param 'animal_health'|'animal_production'|'animal_weight'|'animal_expenses' $table
     * @param array<string,scalar|null> $row  already keyed to the table's columns (no id/farm/animal)
     */
    public function addEvent(string $table, string $farmId, string $animalId, array $row): string
    {
        $allowed = ['animal_health', 'animal_production', 'animal_weight', 'animal_expenses'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('bad table');
        }
        $id = Ulid::generate();
        $payload = ['id' => $id, 'farm_id' => $farmId, 'animal_id' => $animalId] + $row;
        if ($table !== 'animal_weight') {
            $payload['created_at'] = Dates::nowUtc();
        }
        $this->db->insert($table, $payload);
        return $id;
    }

    public function deleteEvent(string $table, string $farmId, string $id): int
    {
        $allowed = ['animal_health', 'animal_production', 'animal_weight', 'animal_expenses', 'animal_sales'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('bad table');
        }
        return $this->db->delete($table, ['id' => $id, 'farm_id' => $farmId]);
    }

    /** @param array<string,scalar|null> $row */
    public function addSale(string $farmId, string $animalId, array $row, ?string $transactionId): string
    {
        $id = Ulid::generate();
        $this->db->insert('animal_sales', [
            'id' => $id, 'farm_id' => $farmId, 'animal_id' => $animalId,
            'transaction_id' => $transactionId,
            'sale_date' => $row['sale_date'], 'quantity' => $row['quantity'],
            'weight_at_sale' => $row['weight_at_sale'], 'price_per_kg' => $row['price_per_kg'],
            'total_amount' => $row['total_amount'], 'buyer' => $row['buyer'], 'notes' => $row['notes'],
            'created_at' => Dates::nowUtc(),
        ]);
        return $id;
    }
}
