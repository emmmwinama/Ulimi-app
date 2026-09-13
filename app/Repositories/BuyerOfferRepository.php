<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class BuyerOfferRepository
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
            'SELECT bo.*, b.name AS buyer_name
             FROM buyer_offers bo LEFT JOIN buyers b ON b.id = bo.buyer_id
             WHERE bo.farm_id = :fid
             ORDER BY FIELD(bo.status, \'open\', \'accepted\', \'declined\', \'expired\'), bo.created_at DESC',
            ['fid' => $farmId],
        );
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM buyer_offers WHERE id = :id AND farm_id = :fid LIMIT 1', ['id' => $id, 'fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('buyer_offers', [
            'id'              => $id,
            'farm_id'         => $farmId,
            'buyer_id'        => $data['buyer_id'],
            'crop_name'       => $data['crop_name'],
            'quantity_wanted' => $data['quantity_wanted'],
            'unit'            => $data['unit'],
            'price_offered'   => $data['price_offered'],
            'status'          => 'open',
            'expiry_date'     => $data['expiry_date'],
            'notes'           => $data['notes'],
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
        return $id;
    }

    public function updateStatus(string $farmId, string $id, string $status): void
    {
        $this->db->update('buyer_offers', ['status' => $status, 'updated_at' => Dates::nowUtc()], ['id' => $id, 'farm_id' => $farmId]);
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('buyer_offers', ['id' => $id, 'farm_id' => $farmId]);
    }
}
