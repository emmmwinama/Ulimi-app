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
    public function __construct(private readonly NotificationRepository $notifications = new NotificationRepository())
    {
    }

    public function run(string $userId, string $farmId): void
    {
        $this->harvestsDue($userId, $farmId);
        $this->noRecentActivity($userId, $farmId);
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
}
