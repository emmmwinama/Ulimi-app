<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

final class InquiryRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /* --------------------------------------------------------------- contact */

    /** @return array<int,array<string,mixed>> */
    public function contacts(string $status = ''): array
    {
        $sql = 'SELECT * FROM contact_submissions';
        $bind = [];
        if ($status !== '') {
            $sql .= ' WHERE status = :status';
            $bind['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        return $this->db->select($sql, $bind);
    }

    /** @param array{name:string,email:string,message:string} $data */
    public function createContact(array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('contact_submissions', [
            'id' => $id, 'name' => $data['name'], 'email' => $data['email'], 'message' => $data['message'],
            'status' => 'new', 'notes' => null, 'created_at' => Dates::nowUtc(), 'replied_at' => null,
        ]);
        return $id;
    }

    public function updateContactStatus(string $id, string $status): void
    {
        $update = ['status' => $status];
        if ($status === 'replied') {
            $update['replied_at'] = Dates::nowUtc();
        }
        $this->db->update('contact_submissions', $update, ['id' => $id]);
    }

    public function unreadContactCount(): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM contact_submissions WHERE status = 'new'");
    }

    /* ----------------------------------------------------------------- demo */

    /** @return array<int,array<string,mixed>> */
    public function demos(string $status = ''): array
    {
        $sql = 'SELECT * FROM demo_bookings';
        $bind = [];
        if ($status !== '') {
            $sql .= ' WHERE status = :status';
            $bind['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        return $this->db->select($sql, $bind);
    }

    /** @param array{name:string,email:string,farm:string,message:?string} $data */
    public function createDemo(array $data): string
    {
        $id = Ulid::generate();
        $this->db->insert('demo_bookings', [
            'id' => $id, 'name' => $data['name'], 'email' => $data['email'], 'farm' => $data['farm'],
            'message' => $data['message'], 'status' => 'pending', 'notes' => null,
            'booked_for' => null, 'created_at' => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function updateDemoStatus(string $id, string $status): void
    {
        $this->db->update('demo_bookings', ['status' => $status], ['id' => $id]);
    }

    public function pendingDemoCount(): int
    {
        return (int) $this->db->scalar("SELECT COUNT(*) FROM demo_bookings WHERE status = 'pending'");
    }
}
