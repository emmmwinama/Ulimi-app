<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class DocumentRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<int,array<string,mixed>> */
    public function forFarm(string $farmId, ?string $type = null): array
    {
        $sql = 'SELECT d.*, u.name AS uploaded_by_name FROM farm_documents d
                LEFT JOIN users u ON u.id = d.uploaded_by WHERE d.farm_id = :fid';
        $bind = ['fid' => $farmId];
        if ($type !== null && $type !== '') {
            $sql .= ' AND d.type = :type';
            $bind['type'] = $type;
        }
        $sql .= ' ORDER BY d.uploaded_at DESC';
        return $this->db->select($sql, $bind);
    }

    /** @return array<string,mixed>|null */
    public function find(string $farmId, string $id): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM farm_documents WHERE id = :id AND farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
    }

    public function countForFarm(string $farmId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM farm_documents WHERE farm_id = :fid', ['fid' => $farmId]);
    }

    /** @param array<string,mixed> $data */
    public function create(string $farmId, string $userId, array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('farm_documents', [
            'id' => $id, 'farm_id' => $farmId,
            'name' => $data['name'], 'type' => $data['type'],
            'asset_id' => $data['asset_id'], 'mime_type' => $data['mime_type'], 'size' => $data['size'],
            'linked_to' => $data['linked_to'] ?? null, 'linked_type' => $data['linked_type'] ?? null,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $userId, 'uploaded_at' => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('farm_documents', ['id' => $id, 'farm_id' => $farmId]);
    }
}
