<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\NotificationRepository;

/**
 * Lazily derives notifications from the farm's current data. There is no cron
 * on the target host, so this runs on-demand (dashboard / notifications page
 * load) rather than on a schedule. Each notification has a stable dedupe key
 * so re-running never creates duplicates — NotificationRepository::upsert()
 * is a no-op when the key already exists.
 */
final class NotificationGenerator
{
    public function __construct(
        private readonly NotificationRepository $notifications = new NotificationRepository(),
        private readonly Weather $weather = new Weather(),
    ) {
    }

    /** @param array<string,mixed> $farm */
    public function run(string $userId, string $farmId, array $farm): void
    {
        $this->harvestsDue($userId, $farmId);
        $this->storageReminder($userId, $farmId);
        $this->noRecentActivity($userId, $farmId);
        $this->lowStock($userId, $farmId);
        $this->expiringStock($userId, $farmId);
        $this->priceAlert($userId, $farmId);
        $this->livestockDue($userId, $farmId);
        $this->weatherAlert($userId, $farmId, $farm);
        $this->outbreakAlert($userId, $farmId);
        $this->notifications->pruneStale($userId);
    }

    /** Active plantings with expected harvest within 7 days and no yield logged yet. */
    private function harvestsDue(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT cf.id, ct.name AS crop_name, f.name AS field_name, cf.expected_harvest_date
             FROM crop_fields cf
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE cf.farm_id = :fid AND cf.is_archived = 0 AND cf.status = 'Active'
               AND cf.expected_harvest_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
               AND NOT EXISTS (SELECT 1 FROM harvest_yields hy WHERE hy.crop_field_id = cf.id)",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'harvest_due',
                'dedupe_key' => 'harvest_due:' . $r['id'] . ':' . $r['expected_harvest_date'],
                'title' => 'Harvest due soon',
                'message' => (string) $r['crop_name'] . ' on ' . (string) $r['field_name'] . ' is expected to be ready around ' . (string) $r['expected_harvest_date'] . '.',
                'link' => '/crops/' . $r['id'],
            ]);
        }
    }

    /** Active plantings with no logged activity in the last 21 days. */
    private function noRecentActivity(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT cf.id, ct.name AS crop_name, f.name AS field_name
             FROM crop_fields cf
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE cf.farm_id = :fid AND cf.is_archived = 0 AND cf.status = 'Active'
               AND cf.planting_date < DATE_SUB(CURDATE(), INTERVAL 21 DAY)
               AND NOT EXISTS (
                 SELECT 1 FROM farm_activities a
                 WHERE a.crop_field_id = cf.id AND a.date >= DATE_SUB(CURDATE(), INTERVAL 21 DAY)
               )",
            ['fid' => $farmId],
        );

        $today = date('Y-m-d');
        foreach ($rows as $r) {
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'no_activity',
                // Weekly-rotating key so the reminder resurfaces instead of staying dismissed forever.
                'dedupe_key' => 'no_activity:' . $r['id'] . ':' . date('o-\WW', strtotime($today)),
                'title' => 'No recent activity',
                'message' => 'No activity logged for ' . (string) $r['crop_name'] . ' on ' . (string) $r['field_name'] . ' in the last 3 weeks.',
                'link' => '/crops/' . $r['id'],
            ]);
        }
    }

    /** Harvests logged in the last 14 days with no storage/drying/loss record yet. */
    private function storageReminder(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT hy.id, ct.name AS crop_name, f.name AS field_name
             FROM harvest_yields hy
             JOIN crop_fields cf ON cf.id = hy.crop_field_id
             JOIN crop_types ct ON ct.id = cf.crop_type_id
             JOIN fields f ON f.id = cf.field_id
             WHERE hy.farm_id = :fid
               AND hy.harvest_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
               AND NOT EXISTS (SELECT 1 FROM produce_storage ps WHERE ps.harvest_yield_id = hy.id)",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'storage_reminder',
                'dedupe_key' => 'storage_reminder:' . $r['id'],
                'title' => 'Log storage details',
                'message' => 'Record how ' . (string) $r['crop_name'] . ' from ' . (string) $r['field_name'] . ' was stored or dried to track post-harvest losses.',
                'link' => '/yields/' . $r['id'] . '/storage',
            ]);
        }
    }

    /** Stock items at or below their (optional) reorder threshold. */
    private function lowStock(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT id, name, quantity, unit
             FROM inventory_items
             WHERE farm_id = :fid AND reorder_threshold IS NOT NULL AND quantity <= reorder_threshold",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'low_inventory',
                // Weekly-rotating key so the alert resurfaces while stock stays low.
                'dedupe_key' => 'low_inventory:' . $r['id'] . ':' . date('o-\WW'),
                'title' => 'Low stock',
                'message' => (string) $r['name'] . ' is down to ' . rtrim(rtrim((string) $r['quantity'], '0'), '.') . ' ' . (string) $r['unit'] . ' — consider reordering.',
                'link' => '/inventory',
            ]);
        }
    }

    /** Stock items with an expiry date within 30 days (or already past it) and still in stock. */
    private function expiringStock(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT id, name, expiry_date
             FROM inventory_items
             WHERE farm_id = :fid AND quantity > 0 AND expiry_date IS NOT NULL
               AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $expired = strtotime((string) $r['expiry_date']) < strtotime('today');
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'expiring_stock',
                'dedupe_key' => 'expiring_stock:' . $r['id'] . ':' . $r['expiry_date'],
                'title' => $expired ? 'Stock expired' : 'Stock expiring soon',
                'message' => (string) $r['name'] . ($expired ? ' expired on ' : ' expires on ') . (string) $r['expiry_date'] . '.',
                'link' => '/inventory',
            ]);
        }
    }

    /** Recent sales priced notably below the current market average for the same item name. */
    private function priceAlert(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT s.id, i.name AS item_name, s.price_per_unit, s.sale_date, mp.price_avg, mp.unit
             FROM inventory_sales s
             JOIN inventory_items i ON i.id = s.inventory_item_id
             JOIN market_prices mp ON mp.is_active = 1 AND LOWER(mp.crop_name) = LOWER(i.name)
             WHERE s.farm_id = :fid
               AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
               AND mp.price_avg > 0
               AND s.price_per_unit < mp.price_avg * 0.85",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'price_alert',
                'dedupe_key' => 'price_alert:' . $r['id'],
                'title' => 'Sold below market price',
                'message' => (string) $r['item_name'] . ' sold on ' . (string) $r['sale_date'] . ' at ' . (string) $r['price_per_unit']
                    . '/' . (string) $r['unit'] . ' — below the recent market average of ' . (string) $r['price_avg'] . '.',
                'link' => '/market',
            ]);
        }
    }

    /** Active animals whose most recent health record is due or overdue for follow-up. */
    private function livestockDue(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT ah.id, ah.type, ah.next_due_date, a.id AS animal_id, a.name AS animal_name, a.tag
             FROM animal_health ah
             JOIN animals a ON a.id = ah.animal_id AND a.farm_id = :fid AND a.status = 'Active'
             WHERE ah.next_due_date IS NOT NULL
               AND ah.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
               AND ah.id = (
                 SELECT ah2.id FROM animal_health ah2
                 WHERE ah2.animal_id = ah.animal_id
                 ORDER BY ah2.date DESC, ah2.created_at DESC LIMIT 1
               )",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $label = (string) ($r['animal_name'] ?: $r['tag'] ?: 'Animal ' . $r['animal_id']);
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'livestock_due',
                'dedupe_key' => 'livestock_due:' . $r['id'] . ':' . $r['next_due_date'],
                'title' => (string) $r['type'] . ' due',
                'message' => $label . ' is due for ' . mb_strtolower((string) $r['type']) . ' follow-up around ' . (string) $r['next_due_date'] . '.',
                'link' => '/livestock/' . $r['animal_id'],
            ]);
        }
    }

    /** Non-informational weather advice (caution/warning severity) for the farm's location. */
    private function weatherAlert(string $userId, string $farmId, array $farm): void
    {
        $weather = $this->weather->forFarm($farm);
        foreach (Weather::advice($weather) as $a) {
            if ($a['severity'] === 'info') {
                continue;
            }
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'weather_alert',
                // Daily key: a fresh forecast pull is what would change this, not a fixed TTL.
                'dedupe_key' => 'weather_alert:' . $farmId . ':' . date('Y-m-d') . ':' . md5($a['text']),
                'title' => 'Weather alert',
                'message' => $a['text'],
                'link' => '/weather',
            ]);
        }
    }

    /** 3+ pest/disease incidents of the same type reported on this farm in the last 14 days. */
    private function outbreakAlert(string $userId, string $farmId): void
    {
        $db = Database::instance();
        $rows = $db->select(
            "SELECT type, COUNT(*) AS cnt
             FROM crop_incidents
             WHERE farm_id = :fid AND reported_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
             GROUP BY type
             HAVING COUNT(*) >= 3",
            ['fid' => $farmId],
        );

        foreach ($rows as $r) {
            $type = (string) $r['type'];
            $this->notifications->upsert($userId, $farmId, [
                'type' => 'outbreak_alert',
                // Weekly-rotating key so it resurfaces while the outbreak is still active.
                'dedupe_key' => 'outbreak_alert:' . $farmId . ':' . $type . ':' . date('o-\WW'),
                'title' => 'Possible ' . $type . ' outbreak',
                'message' => (string) $r['cnt'] . ' ' . $type . ' incidents reported on your farm in the last 14 days — consider a wider inspection.',
                'link' => '/incidents?type=' . rawurlencode($type),
            ]);
        }
    }
}
