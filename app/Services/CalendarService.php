<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityRepository;
use App\Repositories\CropFieldRepository;

/**
 * Composes one month's worth of farm events — plantings, expected harvests,
 * CropTimeline stage due-dates, and actually logged activities — into a
 * day-keyed array for the calendar view. Purely a read-side aggregation over
 * existing repositories; no new tables.
 */
final class CalendarService
{
    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly CropFieldRepository $cropFields = new CropFieldRepository(),
    ) {
    }

    /**
     * @return array<string,list<array{kind:string,label:string,crop:?string,field:?string}>>
     *         keyed by 'Y-m-d', each day's events in a stable, readable order
     */
    public function forMonth(string $farmId, int $year, int $month): array
    {
        $monthStart = sprintf('%04d-%02d-01', $year, $month);
        $monthEnd = date('Y-m-t', (int) strtotime($monthStart));

        $days = [];
        $add = static function (string $date, array $event) use (&$days, $monthStart, $monthEnd): void {
            if ($date < $monthStart || $date > $monthEnd) {
                return;
            }
            $days[$date] ??= [];
            $days[$date][] = $event;
        };

        foreach ($this->activities->forFarm($farmId, ['from' => $monthStart, 'to' => $monthEnd]) as $a) {
            $add((string) $a['date'], [
                'kind' => 'activity',
                'label' => (string) $a['activity_type'],
                'crop' => $a['crop_name'] !== null ? (string) $a['crop_name'] : null,
                'field' => (string) $a['field_name'],
            ]);
        }

        foreach ($this->cropFields->forFarm($farmId) as $cf) {
            $crop = (string) $cf['crop_name'];
            $field = (string) $cf['field_name'];
            $plantingDate = (string) $cf['planting_date'];

            $add($plantingDate, ['kind' => 'planting', 'label' => 'Planting', 'crop' => $crop, 'field' => $field]);
            $add((string) $cf['expected_harvest_date'], ['kind' => 'harvest', 'label' => 'Expected harvest', 'crop' => $crop, 'field' => $field]);

            if (!CropTimeline::isKnown($crop)) {
                continue;
            }
            foreach (CropTimeline::forPlanting($crop, $plantingDate) as $stage) {
                $add((string) $stage['start'], [
                    'kind' => 'stage',
                    'label' => (string) $stage['stage'],
                    'crop' => $crop,
                    'field' => $field,
                ]);
            }
        }

        ksort($days);
        return $days;
    }
}
