<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class UserRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /** @return array<string,mixed>|null */
    public function find(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower(trim($email))],
        );
    }

    public function emailExists(string $email): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower(trim($email))],
        ) !== false;
    }

    /**
     * @param array{name:?string,email:string,password_hash:string,is_active?:int} $data
     */
    public function create(array $data): string
    {
        $id = Ulid::generate();
        $now = Dates::nowUtc();
        $this->db->insert('users', [
            'id'            => $id,
            'name'          => $data['name'] ?? null,
            'email'         => mb_strtolower(trim($data['email'])),
            'password'      => $data['password_hash'],
            'is_active'     => $data['is_active'] ?? 0,
            'last_login_at' => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
        return $id;
    }

    /** @param array<string,scalar|null> $fields */
    public function update(string $id, array $fields): void
    {
        $fields['updated_at'] = Dates::nowUtc();
        $this->db->update('users', $fields, ['id' => $id]);
    }

    public function activate(string $id, string $passwordHash): void
    {
        $this->update($id, ['is_active' => 1, 'password' => $passwordHash]);
    }

    public function setPassword(string $id, string $passwordHash): void
    {
        $this->update($id, ['password' => $passwordHash]);
    }
}
