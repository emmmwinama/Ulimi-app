<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * Cooperative aggregate: the cooperative itself, its members, contribution
 * ledger, and collective sales (+ per-member splits) — consolidated into one
 * repository the same way LivestockRepository groups an animal's event logs,
 * since these are all facets of one small subsystem rather than independent
 * resources.
 */
final class CooperativeRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /* ------------------------------------------------------------ cooperatives */

    /** Cooperatives the given farm belongs to. @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId): array
    {
        return $this->db->select(
            'SELECT c.*, cm.role
             FROM cooperatives c JOIN cooperative_members cm ON cm.cooperative_id = c.id
             WHERE cm.farm_id = :fid ORDER BY c.name ASC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM cooperatives WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return array<string,mixed>|null */
    public function findByJoinCode(string $code): ?array
    {
        return $this->db->selectOne('SELECT * FROM cooperatives WHERE join_code = :code LIMIT 1', ['code' => strtoupper($code)]);
    }

    /** Creates the cooperative and makes the creating farm its chair. Returns the new cooperative id. */
    public function create(string $farmId, string $userId, string $name, ?string $region): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('cooperatives', [
            'id'            => $id,
            'name'          => $name,
            'region'        => $region,
            'join_code'     => $this->generateJoinCode(),
            'created_by_id' => $userId,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        $this->addMember($id, $farmId, 'chair');
        return $id;
    }

    private function generateJoinCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
        } while ($this->findByJoinCode($code) !== null);
        return $code;
    }

    /* -------------------------------------------------------------- members */

    /** @return array<string,mixed>|null */
    public function membership(string $cooperativeId, string $farmId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM cooperative_members WHERE cooperative_id = :cid AND farm_id = :fid LIMIT 1',
            ['cid' => $cooperativeId, 'fid' => $farmId],
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function members(string $cooperativeId): array
    {
        return $this->db->select(
            'SELECT cm.*, f.name AS farm_name, f.location AS farm_location
             FROM cooperative_members cm JOIN farms f ON f.id = cm.farm_id
             WHERE cm.cooperative_id = :cid ORDER BY FIELD(cm.role, \'chair\',\'secretary\',\'treasurer\',\'member\'), f.name ASC',
            ['cid' => $cooperativeId],
        );
    }

    /** @return list<string> farm ids */
    public function memberFarmIds(string $cooperativeId): array
    {
        return array_map(
            static fn (array $r): string => (string) $r['farm_id'],
            $this->db->select('SELECT farm_id FROM cooperative_members WHERE cooperative_id = :cid', ['cid' => $cooperativeId]),
        );
    }

    public function addMember(string $cooperativeId, string $farmId, string $role = 'member'): string
    {
        $id = Ulid::generate();
        $this->db->insert('cooperative_members', [
            'id'             => $id,
            'cooperative_id' => $cooperativeId,
            'farm_id'        => $farmId,
            'role'           => $role,
            'joined_at'      => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function removeMember(string $cooperativeId, string $farmId): int
    {
        return $this->db->delete('cooperative_members', ['cooperative_id' => $cooperativeId, 'farm_id' => $farmId]);
    }

    public function memberCount(string $cooperativeId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM cooperative_members WHERE cooperative_id = :cid', ['cid' => $cooperativeId]);
    }

    /* --------------------------------------------------------- contributions */

    /** @return array<int,array<string,mixed>> */
    public function contributions(string $cooperativeId): array
    {
        return $this->db->select(
            'SELECT cc.*, f.name AS farm_name
             FROM cooperative_contributions cc JOIN farms f ON f.id = cc.farm_id
             WHERE cc.cooperative_id = :cid ORDER BY cc.date DESC',
            ['cid' => $cooperativeId],
        );
    }

    /** @param array<string,mixed> $data */
    public function addContribution(string $cooperativeId, string $userId, array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('cooperative_contributions', [
            'id'             => $id,
            'cooperative_id' => $cooperativeId,
            'farm_id'        => $data['farm_id'],
            'amount'         => $data['amount'],
            'date'           => $data['date'],
            'type'           => $data['type'],
            'notes'          => $data['notes'],
            'created_by_id'  => $userId,
            'created_at'     => Dates::nowUtc(),
        ]);
        return $id;
    }

    /* -------------------------------------------------------------- sales */

    /** @return array<int,array<string,mixed>> */
    public function sales(string $cooperativeId): array
    {
        return $this->db->select(
            'SELECT * FROM cooperative_sales WHERE cooperative_id = :cid ORDER BY sale_date DESC',
            ['cid' => $cooperativeId],
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function saleSplits(string $saleId): array
    {
        return $this->db->select(
            'SELECT css.*, f.name AS farm_name
             FROM cooperative_sale_splits css JOIN farms f ON f.id = css.farm_id
             WHERE css.cooperative_sale_id = :sid ORDER BY f.name ASC',
            ['sid' => $saleId],
        );
    }

    /**
     * @param array<string,mixed> $sale
     * @param list<array{farm_id:string,quantity:float,amount:float}> $splits
     */
    public function addSale(string $cooperativeId, string $userId, array $sale, array $splits): string
    {
        $id = Ulid::generate();
        $this->db->transaction(function () use ($id, $cooperativeId, $userId, $sale, $splits): void {
            $this->db->insert('cooperative_sales', [
                'id'             => $id,
                'cooperative_id' => $cooperativeId,
                'crop_name'      => $sale['crop_name'],
                'total_quantity' => $sale['total_quantity'],
                'unit'           => $sale['unit'],
                'price_per_unit' => $sale['price_per_unit'],
                'total_amount'   => $sale['total_amount'],
                'buyer_name'     => $sale['buyer_name'],
                'sale_date'      => $sale['sale_date'],
                'notes'          => $sale['notes'],
                'created_by_id'  => $userId,
                'created_at'     => Dates::nowUtc(),
            ]);
            foreach ($splits as $split) {
                $this->db->insert('cooperative_sale_splits', [
                    'id'                  => Ulid::generate(),
                    'cooperative_sale_id' => $id,
                    'farm_id'             => $split['farm_id'],
                    'quantity'            => $split['quantity'],
                    'amount'              => $split['amount'],
                ]);
            }
        });
        return $id;
    }
}
