<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Indicative crop-cycle timelines for the crops AgriVault supports in the
 * Malawi context. Each crop has an approximate days-to-maturity and an ordered
 * set of stages expressed as day offsets from the planting date (negative =
 * before planting). These drive the planting timeline view and the activity-type
 * suggestions in the activity log.
 *
 * These are planning aids, not agronomic advice — actual timing depends on
 * variety, rainfall and management. The numbers are deliberately round.
 */
final class CropTimeline
{
    /**
     * name => [maturityDays, [ [stageLabel, activityType, dayFrom, dayTo], ... ] ]
     * A day range is relative to the planting date.
     *
     * @var array<string,array{0:int,1:list<array{0:string,1:string,2:int,3:int}>}>
     */
    private const CROPS = [
        'Maize' => [140, [
            ['Land preparation', 'Land Preparation', -25, -5],
            ['Planting', 'Planting', 0, 5],
            ['Basal fertiliser', 'Fertiliser Application', 3, 12],
            ['First weeding', 'Weeding', 18, 28],
            ['Top-dressing (N)', 'Fertiliser Application', 28, 40],
            ['Second weeding', 'Weeding', 40, 55],
            ['Fall armyworm scouting', 'Pest & Disease Control', 20, 90],
            ['Physiological maturity', 'Monitoring', 120, 135],
            ['Harvest', 'Harvesting', 138, 155],
            ['Drying & shelling', 'Post-Harvest Handling', 150, 175],
            ['Storage', 'Storage', 165, 200],
        ]],
        'Soybean' => [120, [
            ['Land preparation', 'Land Preparation', -20, -3],
            ['Inoculation & planting', 'Planting', 0, 4],
            ['First weeding', 'Weeding', 15, 25],
            ['Second weeding', 'Weeding', 35, 45],
            ['Pest scouting', 'Pest & Disease Control', 25, 90],
            ['Harvest', 'Harvesting', 115, 130],
            ['Threshing & drying', 'Post-Harvest Handling', 122, 140],
            ['Storage', 'Storage', 135, 170],
        ]],
        'Groundnut' => [130, [
            ['Land preparation', 'Land Preparation', -20, -3],
            ['Planting', 'Planting', 0, 5],
            ['First weeding', 'Weeding', 18, 28],
            ['Gypsum / earthing up', 'Fertiliser Application', 35, 50],
            ['Second weeding', 'Weeding', 45, 60],
            ['Leaf spot scouting', 'Pest & Disease Control', 40, 100],
            ['Harvest & lifting', 'Harvesting', 125, 140],
            ['Drying & stripping', 'Post-Harvest Handling', 130, 155],
            ['Storage', 'Storage', 150, 190],
        ]],
        'Tobacco' => [150, [
            ['Nursery management', 'Nursery', -50, -8],
            ['Land preparation', 'Land Preparation', -25, -3],
            ['Transplanting', 'Planting', 0, 6],
            ['Fertiliser application', 'Fertiliser Application', 5, 20],
            ['Weeding & moulding', 'Weeding', 15, 35],
            ['Topping & de-suckering', 'Crop Management', 55, 80],
            ['Pest & disease control', 'Pest & Disease Control', 20, 110],
            ['Reaping', 'Harvesting', 90, 140],
            ['Curing', 'Post-Harvest Handling', 95, 160],
            ['Grading & baling', 'Post-Harvest Handling', 140, 185],
        ]],
        'Rice' => [135, [
            ['Land preparation & puddling', 'Land Preparation', -20, -3],
            ['Sowing / transplanting', 'Planting', 0, 7],
            ['Gap filling & weeding', 'Weeding', 15, 30],
            ['Top-dressing', 'Fertiliser Application', 25, 40],
            ['Second weeding', 'Weeding', 40, 55],
            ['Water & pest management', 'Pest & Disease Control', 10, 100],
            ['Harvest', 'Harvesting', 125, 140],
            ['Threshing & drying', 'Post-Harvest Handling', 130, 150],
            ['Storage', 'Storage', 145, 185],
        ]],
        'Sorghum' => [130, [
            ['Land preparation', 'Land Preparation', -20, -3],
            ['Planting', 'Planting', 0, 5],
            ['Thinning & first weeding', 'Weeding', 15, 28],
            ['Top-dressing', 'Fertiliser Application', 25, 38],
            ['Bird scaring', 'Crop Management', 95, 128],
            ['Harvest', 'Harvesting', 125, 140],
            ['Drying & threshing', 'Post-Harvest Handling', 130, 150],
            ['Storage', 'Storage', 145, 185],
        ]],
        'Millet' => [110, [
            ['Land preparation', 'Land Preparation', -18, -3],
            ['Planting', 'Planting', 0, 5],
            ['Thinning & weeding', 'Weeding', 14, 26],
            ['Top-dressing', 'Fertiliser Application', 22, 34],
            ['Bird scaring', 'Crop Management', 80, 108],
            ['Harvest', 'Harvesting', 105, 120],
            ['Drying & threshing', 'Post-Harvest Handling', 110, 130],
            ['Storage', 'Storage', 125, 165],
        ]],
        'Sunflower' => [110, [
            ['Land preparation', 'Land Preparation', -18, -3],
            ['Planting', 'Planting', 0, 5],
            ['First weeding', 'Weeding', 15, 25],
            ['Top-dressing', 'Fertiliser Application', 22, 34],
            ['Bird & pest control', 'Pest & Disease Control', 70, 105],
            ['Harvest', 'Harvesting', 105, 120],
            ['Drying & threshing', 'Post-Harvest Handling', 110, 128],
            ['Storage', 'Storage', 122, 160],
        ]],
        'Cassava' => [330, [
            ['Land preparation', 'Land Preparation', -25, -3],
            ['Planting cuttings', 'Planting', 0, 7],
            ['First weeding', 'Weeding', 20, 40],
            ['Second weeding', 'Weeding', 60, 85],
            ['Third weeding', 'Weeding', 120, 150],
            ['Pest & disease scouting', 'Pest & Disease Control', 30, 300],
            ['Harvest (piecemeal)', 'Harvesting', 300, 360],
        ]],
        'Sweet Potato' => [150, [
            ['Land preparation & ridging', 'Land Preparation', -20, -3],
            ['Planting vines', 'Planting', 0, 6],
            ['First weeding', 'Weeding', 18, 30],
            ['Re-ridging / moulding', 'Crop Management', 35, 50],
            ['Weevil scouting', 'Pest & Disease Control', 40, 130],
            ['Harvest', 'Harvesting', 140, 165],
            ['Curing & storage', 'Post-Harvest Handling', 145, 180],
        ]],
        'Irish Potato' => [110, [
            ['Land preparation & ridging', 'Land Preparation', -18, -3],
            ['Planting seed tubers', 'Planting', 0, 5],
            ['Earthing up & weeding', 'Weeding', 20, 35],
            ['Top-dressing', 'Fertiliser Application', 25, 40],
            ['Blight spraying', 'Pest & Disease Control', 30, 95],
            ['Haulm cutting', 'Crop Management', 95, 105],
            ['Harvest', 'Harvesting', 105, 120],
            ['Curing & storage', 'Post-Harvest Handling', 110, 140],
        ]],
        'Beans' => [95, [
            ['Land preparation', 'Land Preparation', -15, -3],
            ['Planting', 'Planting', 0, 4],
            ['First weeding', 'Weeding', 12, 22],
            ['Second weeding', 'Weeding', 28, 38],
            ['Pest & disease control', 'Pest & Disease Control', 20, 75],
            ['Harvest', 'Harvesting', 88, 100],
            ['Drying & threshing', 'Post-Harvest Handling', 92, 108],
            ['Storage', 'Storage', 103, 140],
        ]],
        'Cowpea' => [90, [
            ['Land preparation', 'Land Preparation', -15, -3],
            ['Planting', 'Planting', 0, 4],
            ['First weeding', 'Weeding', 12, 22],
            ['Aphid & thrips control', 'Pest & Disease Control', 18, 70],
            ['Harvest (multiple picks)', 'Harvesting', 75, 100],
            ['Drying & threshing', 'Post-Harvest Handling', 82, 108],
            ['Storage', 'Storage', 100, 135],
        ]],
        'Pigeon Pea' => [220, [
            ['Land preparation', 'Land Preparation', -20, -3],
            ['Planting', 'Planting', 0, 6],
            ['First weeding', 'Weeding', 20, 35],
            ['Second weeding', 'Weeding', 55, 75],
            ['Pod borer scouting', 'Pest & Disease Control', 90, 200],
            ['Harvest', 'Harvesting', 200, 230],
            ['Drying & threshing', 'Post-Harvest Handling', 210, 240],
            ['Storage', 'Storage', 230, 270],
        ]],
        'Cotton' => [170, [
            ['Land preparation', 'Land Preparation', -20, -3],
            ['Planting', 'Planting', 0, 6],
            ['Thinning & first weeding', 'Weeding', 15, 28],
            ['Top-dressing', 'Fertiliser Application', 30, 45],
            ['Bollworm spraying', 'Pest & Disease Control', 40, 150],
            ['Harvest (picking rounds)', 'Harvesting', 150, 185],
            ['Grading & storage', 'Post-Harvest Handling', 160, 195],
        ]],
        'Sugarcane' => [365, [
            ['Land preparation', 'Land Preparation', -30, -5],
            ['Planting setts', 'Planting', 0, 10],
            ['Gap filling & first weeding', 'Weeding', 25, 50],
            ['Fertiliser application', 'Fertiliser Application', 30, 70],
            ['Earthing up', 'Crop Management', 90, 130],
            ['Pest & disease scouting', 'Pest & Disease Control', 60, 330],
            ['Harvest', 'Harvesting', 340, 380],
        ]],
        'Banana' => [300, [
            ['Land preparation & holing', 'Land Preparation', -30, -5],
            ['Planting suckers', 'Planting', 0, 10],
            ['Mulching & weeding', 'Weeding', 20, 60],
            ['De-suckering & de-leafing', 'Crop Management', 60, 250],
            ['Fertiliser application', 'Fertiliser Application', 30, 200],
            ['Bunch management', 'Crop Management', 210, 280],
            ['Harvest', 'Harvesting', 280, 320],
        ]],
        'Tomato' => [110, [
            ['Nursery management', 'Nursery', -35, -5],
            ['Land preparation', 'Land Preparation', -18, -3],
            ['Transplanting', 'Planting', 0, 5],
            ['Staking & first weeding', 'Crop Management', 15, 28],
            ['Fertiliser application', 'Fertiliser Application', 12, 35],
            ['Blight & pest spraying', 'Pest & Disease Control', 15, 95],
            ['Harvest (multiple picks)', 'Harvesting', 75, 110],
            ['Grading & sales', 'Post-Harvest Handling', 78, 115],
        ]],
        'Onion' => [140, [
            ['Nursery management', 'Nursery', -50, -8],
            ['Land preparation', 'Land Preparation', -18, -3],
            ['Transplanting', 'Planting', 0, 6],
            ['First weeding', 'Weeding', 18, 30],
            ['Top-dressing', 'Fertiliser Application', 25, 45],
            ['Thrips & disease control', 'Pest & Disease Control', 20, 120],
            ['Harvest', 'Harvesting', 130, 150],
            ['Curing & storage', 'Post-Harvest Handling', 135, 175],
        ]],
        'Cabbage' => [100, [
            ['Nursery management', 'Nursery', -30, -5],
            ['Land preparation', 'Land Preparation', -16, -3],
            ['Transplanting', 'Planting', 0, 5],
            ['First weeding & gap filling', 'Weeding', 12, 24],
            ['Top-dressing', 'Fertiliser Application', 18, 35],
            ['Diamondback moth control', 'Pest & Disease Control', 15, 90],
            ['Harvest', 'Harvesting', 90, 110],
            ['Grading & sales', 'Post-Harvest Handling', 92, 115],
        ]],
    ];

    /** Days to maturity for a crop, or a sensible default. */
    public static function maturityDays(string $crop): int
    {
        return self::CROPS[self::canonical($crop)][0] ?? 120;
    }

    /** @return list<string> distinct activity types this crop involves, in order */
    public static function activityTypes(string $crop): array
    {
        $stages = self::CROPS[self::canonical($crop)][1] ?? [];
        $types = [];
        foreach ($stages as [, $type]) {
            $types[$type] = true;
        }
        return array_keys($types);
    }

    /**
     * The full stage list for a planting, each with concrete calendar dates.
     *
     * @return list<array{stage:string,activity_type:string,start:string,end:string,state:string}>
     *         state ∈ done|current|upcoming|overdue
     */
    public static function forPlanting(string $crop, string $plantingDate, ?string $today = null): array
    {
        $stages = self::CROPS[self::canonical($crop)][1] ?? [];
        $plantTs = strtotime($plantingDate . ' UTC');
        if ($plantTs === false) {
            return [];
        }
        $nowTs = $today !== null ? (int) strtotime($today . ' UTC') : time();
        $dayNow = (int) floor(($nowTs - $plantTs) / 86400);

        $out = [];
        foreach ($stages as [$label, $type, $from, $to]) {
            $state = match (true) {
                $dayNow > $to                  => 'done',
                $dayNow >= $from && $dayNow <= $to => 'current',
                default                        => 'upcoming',
            };
            $out[] = [
                'stage'         => $label,
                'activity_type' => $type,
                'start'         => gmdate('Y-m-d', $plantTs + $from * 86400),
                'end'           => gmdate('Y-m-d', $plantTs + $to * 86400),
                'state'         => $state,
            ];
        }
        return $out;
    }

    /** True when this crop name has a built-in timeline. */
    public static function isKnown(string $crop): bool
    {
        return isset(self::CROPS[self::canonical($crop)]);
    }

    private static function canonical(string $crop): string
    {
        $crop = ucwords(strtolower(trim($crop)));
        return match ($crop) {
            'Maize (Corn)', 'Corn' => 'Maize',
            'Soya', 'Soya Bean', 'Soyabean' => 'Soybean',
            'Groundnuts', 'Peanut', 'Peanuts' => 'Groundnut',
            'Irish Potatoes', 'Potato' => 'Irish Potato',
            'Sweet Potatoes' => 'Sweet Potato',
            default => $crop,
        };
    }
}
