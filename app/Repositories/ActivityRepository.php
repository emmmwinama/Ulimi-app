<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\Dates;
use App\Support\Ulid;

/**
 * Field activities with their nested cost lines (labour, inputs, other costs).
 * On update the child rows are replaced wholesale inside a transaction — the
 * volume per activity is tiny and this keeps the write path simple and correct.
 */
final class ActivityRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    /**
     * @param array{field_id?:string,crop_field_id?:string,type?:string,from?:string,to?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function forFarm(string $farmId, array $filters = []): array
    {
        $where = ['a.farm_id = :fid'];
        $bind = ['fid' => $farmId];

        foreach (['field_id' => 'a.field_id', 'crop_field_id' => 'a.crop_field_id'] as $k => $col) {
            if (!empty($filters[$k])) {
                $where[] = "{$col} = :{$k}";
                $bind[$k] = $filters[$k];
            }
        }
        if (!empty($filters['type'])) {
            $where[] = 'a.activity_type = :type';
            $bind['type'] = $filters['type'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'a.date >= :from';
            $bind['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'a.date <= :to';
            $bind['to'] = $filters['to'];
        }

        return $this->db->select(
            'SELECT a.*, f.name AS field_name, ct.name AS crop_name,
                    COALESCE(l.sum, 0) + COALESCE(i.sum, 0) + COALESCE(o.sum, 0) AS total_cost
             FROM farm_activities a
             JOIN fields f ON f.id = a.field_id
             LEFT JOIN crop_fields cf ON cf.id = a.crop_field_id
             LEFT JOIN crop_types ct ON ct.id = cf.crop_type_id
             LEFT JOIN (SELECT activity_id, SUM(total_cost) sum FROM activity_labour GROUP BY activity_id) l ON l.activity_id = a.id
             LEFT JOIN (SELECT activity_id, SUM(total_cost) sum FROM activity_inputs GROUP BY activity_id) i ON i.activity_id = a.id
             LEFT JOIN (SELECT activity_id, SUM(amount) sum FROM activity_other_costs GROUP BY activity_id) o ON o.activity_id = a.id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY a.date DESC, a.created_at DESC',
            $bind,
        );
    }

    /** @return array<string,mixed>|null activity plus `labour`, `inputs`, `other` arrays */
    public function findFull(string $farmId, string $id): ?array
    {
        $activity = $this->db->selectOne(
            'SELECT a.*, f.name AS field_name, ct.name AS crop_name, e.name AS responsible_employee_name
             FROM farm_activities a
             JOIN fields f ON f.id = a.field_id
             LEFT JOIN crop_fields cf ON cf.id = a.crop_field_id
             LEFT JOIN crop_types ct ON ct.id = cf.crop_type_id
             LEFT JOIN employees e ON e.id = a.responsible_employee_id
             WHERE a.id = :id AND a.farm_id = :fid LIMIT 1',
            ['id' => $id, 'fid' => $farmId],
        );
        if ($activity === null) {
            return null;
        }

        $activity['labour'] = $this->db->select(
            'SELECT al.*, e.name AS employee_name
             FROM activity_labour al
             LEFT JOIN employees e ON e.id = al.employee_id
             WHERE al.activity_id = :id',
            ['id' => $id],
        );
        $activity['inputs'] = $this->db->select(
            'SELECT * FROM activity_inputs WHERE activity_id = :id',
            ['id' => $id],
        );
        $activity['other'] = $this->db->select(
            'SELECT * FROM activity_other_costs WHERE activity_id = :id',
            ['id' => $id],
        );
        return $activity;
    }

    public function countForFarm(string $farmId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM farm_activities WHERE farm_id = :fid', ['fid' => $farmId]);
    }

    /**
     * @param array<string,mixed> $core   the farm_activities column values (no id/timestamps)
     * @param list<array<string,mixed>> $labour
     * @param list<array<string,mixed>> $inputs
     * @param list<array<string,mixed>> $other
     */
    public function create(string $farmId, string $userId, array $core, array $labour, array $inputs, array $other): string
    {
        return $this->db->transaction(function () use ($farmId, $userId, $core, $labour, $inputs, $other): string {
            $id = Ulid::generate();
            $now = Dates::nowUtc();
            $this->db->insert('farm_activities', [
                'id'                      => $id,
                'farm_id'                 => $farmId,
                'field_id'                => $core['field_id'],
                'crop_field_id'           => $core['crop_field_id'],
                'activity_type'           => $core['activity_type'],
                'date'                    => $core['date'],
                'notes'                   => $core['notes'],
                'responsible_person_name' => $core['responsible_person_name'],
                'responsible_employee_id' => $core['responsible_employee_id'],
                'created_by_id'           => $userId,
                'created_at'              => $now,
                'updated_at'              => $now,
            ]);
            $this->writeLines($farmId, $id, $labour, $inputs, $other);
            return $id;
        });
    }

    /**
     * @param array<string,mixed> $core
     * @param list<array<string,mixed>> $labour
     * @param list<array<string,mixed>> $inputs
     * @param list<array<string,mixed>> $other
     */
    public function update(string $farmId, string $id, array $core, array $labour, array $inputs, array $other): void
    {
        $this->db->transaction(function () use ($farmId, $id, $core, $labour, $inputs, $other): void {
            $this->db->update('farm_activities', [
                'crop_field_id'           => $core['crop_field_id'],
                'activity_type'           => $core['activity_type'],
                'date'                    => $core['date'],
                'notes'                   => $core['notes'],
                'responsible_person_name' => $core['responsible_person_name'],
                'responsible_employee_id' => $core['responsible_employee_id'],
                'updated_at'              => Dates::nowUtc(),
            ], ['id' => $id, 'farm_id' => $farmId]);

            $this->db->delete('activity_labour', ['activity_id' => $id]);
            $this->db->delete('activity_inputs', ['activity_id' => $id]);
            $this->db->delete('activity_other_costs', ['activity_id' => $id]);
            $this->writeLines($farmId, $id, $labour, $inputs, $other);
        });
    }

    public function delete(string $farmId, string $id): int
    {
        return $this->db->delete('farm_activities', ['id' => $id, 'farm_id' => $farmId]);
    }

    /**
     * @param list<array<string,mixed>> $labour
     * @param list<array<string,mixed>> $inputs
     * @param list<array<string,mixed>> $other
     */
    private function writeLines(string $farmId, string $activityId, array $labour, array $inputs, array $other): void
    {
        foreach ($labour as $row) {
            $this->db->insert('activity_labour', [
                'id'           => Ulid::generate(),
                'farm_id'      => $farmId,
                'activity_id'  => $activityId,
                'employee_id'  => $row['employee_id'] ?: null,
                'worker_name'  => $row['worker_name'] ?: null,
                'hours_worked' => $row['hours_worked'],
                'days_worked'  => $row['days_worked'],
                'total_cost'   => $row['total_cost'],
            ]);
        }
        foreach ($inputs as $row) {
            $this->db->insert('activity_inputs', [
                'id'                    => Ulid::generate(),
                'farm_id'               => $farmId,
                'activity_id'           => $activityId,
                'input_name'            => $row['input_name'],
                'category'              => $row['category'],
                'quantity'              => $row['quantity'],
                'unit'                  => $row['unit'],
                'unit_cost'             => $row['unit_cost'],
                'total_cost'            => $row['total_cost'],
                'acquisition_unit_cost' => $row['acquisition_unit_cost'] ?? null,
                'time_value_cost'       => $row['time_value_cost'] ?? null,
                'inventory_item_id'     => $row['inventory_item_id'] ?? null,
            ]);
        }
        foreach ($other as $row) {
            $this->db->insert('activity_other_costs', [
                'id'          => Ulid::generate(),
                'farm_id'     => $farmId,
                'activity_id' => $activityId,
                'description' => $row['description'],
                'amount'      => $row['amount'],
            ]);
        }
    }
}
