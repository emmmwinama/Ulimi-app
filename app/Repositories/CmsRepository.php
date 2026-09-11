<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * The public marketing site's editable content: key/value settings, static
 * pages, feature tiles, and testimonials. Consolidated into one repository
 * since each piece is a small, independent table with no cross-references.
 */
final class CmsRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /* ------------------------------------------------------------- content */

    /** @return array<string,string> key => value, for the whole site */
    public function allContent(): array
    {
        $rows = $this->db->select('SELECT `key`, `value` FROM site_content');
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['key']] = (string) $r['value'];
        }
        return $out;
    }

    public function content(string $key, string $default = ''): string
    {
        $v = $this->db->scalar('SELECT `value` FROM site_content WHERE `key` = :k', ['k' => $key]);
        return $v === false ? $default : (string) $v;
    }

    /** @return array<int,array<string,mixed>> */
    public function allContentRows(): array
    {
        return $this->db->select('SELECT * FROM site_content ORDER BY `group` ASC, `key` ASC');
    }

    public function setContent(string $key, string $value, ?string $adminId): void
    {
        $exists = $this->db->scalar('SELECT 1 FROM site_content WHERE `key` = :k', ['k' => $key]);
        if ($exists !== false) {
            $this->db->update('site_content', ['value' => $value, 'updated_at' => Dates::nowUtc(), 'updated_by' => $adminId], ['key' => $key]);
        } else {
            $this->db->insert('site_content', [
                'key' => $key, 'value' => $value, 'type' => 'text', 'group' => 'general',
                'label' => $key, 'updated_at' => Dates::nowUtc(), 'updated_by' => $adminId,
            ]);
        }
    }

    /* --------------------------------------------------------------- pages */

    /** @return array<int,array<string,mixed>> */
    public function pages(): array
    {
        return $this->db->select('SELECT * FROM cms_pages ORDER BY slug ASC');
    }

    /** @return array<string,mixed>|null */
    public function pageBySlug(string $slug): ?array
    {
        return $this->db->selectOne('SELECT * FROM cms_pages WHERE slug = :s AND is_public = 1 LIMIT 1', ['s' => $slug]);
    }

    /** @return array<string,mixed>|null */
    public function findPage(string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM cms_pages WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function upsertPage(?string $id, string $slug, string $title, string $content, bool $isPublic): void
    {
        if ($id !== null) {
            $this->db->update('cms_pages', [
                'slug' => $slug, 'title' => $title, 'content' => $content,
                'is_public' => $isPublic ? 1 : 0, 'updated_at' => Dates::nowUtc(),
            ], ['id' => $id]);
            return;
        }
        $this->db->insert('cms_pages', [
            'id' => Ulid::generate(), 'slug' => $slug, 'title' => $title, 'content' => $content,
            'is_public' => $isPublic ? 1 : 0, 'updated_at' => Dates::nowUtc(),
        ]);
    }

    public function deletePage(string $id): int
    {
        return $this->db->delete('cms_pages', ['id' => $id]);
    }

    /* ------------------------------------------------------------ features */

    /** @return array<int,array<string,mixed>> */
    public function features(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_features';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC';
        return $this->db->select($sql);
    }

    public function createFeature(string $icon, string $title, string $desc, int $sort): string
    {
        $id = Ulid::generate();
        $this->db->insert('cms_features', ['id' => $id, 'icon' => $icon, 'title' => $title, 'description' => $desc, 'sort_order' => $sort, 'is_active' => 1]);
        return $id;
    }

    public function updateFeature(string $id, string $icon, string $title, string $desc, int $sort, bool $active): void
    {
        $this->db->update('cms_features', ['icon' => $icon, 'title' => $title, 'description' => $desc, 'sort_order' => $sort, 'is_active' => $active ? 1 : 0], ['id' => $id]);
    }

    public function deleteFeature(string $id): int
    {
        return $this->db->delete('cms_features', ['id' => $id]);
    }

    /* --------------------------------------------------------- testimonials */

    /** @return array<int,array<string,mixed>> */
    public function testimonials(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM testimonials';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC';
        return $this->db->select($sql);
    }

    public function createTestimonial(string $quote, string $name, string $role, string $initials, int $sort): string
    {
        $id = Ulid::generate();
        $this->db->insert('testimonials', [
            'id' => $id, 'quote' => $quote, 'name' => $name, 'role' => $role,
            'initials' => $initials, 'is_active' => 1, 'sort_order' => $sort, 'created_at' => Dates::nowUtc(),
        ]);
        return $id;
    }

    public function updateTestimonial(string $id, string $quote, string $name, string $role, string $initials, int $sort, bool $active): void
    {
        $this->db->update('testimonials', [
            'quote' => $quote, 'name' => $name, 'role' => $role,
            'initials' => $initials, 'sort_order' => $sort, 'is_active' => $active ? 1 : 0,
        ], ['id' => $id]);
    }

    public function deleteTestimonial(string $id): int
    {
        return $this->db->delete('testimonials', ['id' => $id]);
    }
}
