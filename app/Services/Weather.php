<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Support\Dates;
use App\Support\Ulid;
use Throwable;

/**
 * Current conditions + 7-day forecast via Open-Meteo (no API key required).
 * Cached per farm for 3 hours in `weather_cache` — a shared host has no cron,
 * so refresh happens lazily whenever a farm's weather page is opened after the
 * cache goes stale.
 */
final class Weather
{
    private const CACHE_TTL_SECONDS = 3 * 3600;
    private const TIMEOUT_SECONDS = 6;

    /** Fallback geocoding for farms without GPS coordinates: known Malawi towns. */
    private const KNOWN_PLACES = [
        'lilongwe' => [-13.9626, 33.7741], 'blantyre' => [-15.7861, 35.0058],
        'mzuzu' => [-11.4381, 34.0150], 'zomba' => [-15.3833, 35.3333],
        'mchinji' => [-13.7975, 32.8809], 'kasungu' => [-13.0333, 33.4833],
        'salima' => [-13.7803, 34.4587], 'karonga' => [-9.9333, 33.9333],
        'mangochi' => [-14.4781, 35.2650], 'dedza' => [-14.3775, 34.3336],
        'ntcheu' => [-14.8167, 34.6333], 'balaka' => [-14.9833, 34.9500],
        'nkhotakota' => [-12.9273, 34.2967], 'rumphi' => [-11.0167, 33.8500],
        'thyolo' => [-16.0667, 35.1333], 'mulanje' => [-16.0333, 35.5167],
    ];

    /**
     * @param array<string,mixed> $farm
     * @return array<string,mixed>
     */
    public function forFarm(array $farm): array
    {
        $db = Database::instance();
        $farmId = (string) $farm['id'];

        $cached = $db->selectOne('SELECT * FROM weather_cache WHERE farm_id = :fid', ['fid' => $farmId]);
        if ($cached !== null) {
            $age = time() - strtotime((string) $cached['cached_at'] . ' UTC');
            if ($age < self::CACHE_TTL_SECONDS) {
                $data = json_decode((string) $cached['data'], true);
                if (is_array($data)) {
                    $data['stale'] = false;
                    $data['cached_at'] = (string) $cached['cached_at'];
                    return $data;
                }
            }
        }

        [$lat, $lng] = $this->resolveLocation($farm);
        $fresh = $this->fetch($lat, $lng);

        if ($fresh === null) {
            // Fetch failed (offline host, DNS blocked, etc.) — serve stale cache if any.
            if ($cached !== null) {
                $data = json_decode((string) $cached['data'], true);
                if (is_array($data)) {
                    $data['stale'] = true;
                    $data['cached_at'] = (string) $cached['cached_at'];
                    return $data;
                }
            }
            return ['available' => false, 'stale' => false, 'cached_at' => null];
        }

        $fresh['available'] = true;
        $payload = json_encode($fresh, JSON_UNESCAPED_SLASHES);
        if ($cached !== null) {
            $db->update('weather_cache', ['data' => $payload, 'cached_at' => Dates::nowUtc()], ['farm_id' => $farmId]);
        } else {
            $db->insert('weather_cache', [
                'id' => Ulid::generate(), 'farm_id' => $farmId, 'data' => $payload, 'cached_at' => Dates::nowUtc(),
            ]);
        }

        $fresh['stale'] = false;
        $fresh['cached_at'] = Dates::nowUtc();
        return $fresh;
    }

    /** @param array<string,mixed> $farm @return array{0:float,1:float} */
    private function resolveLocation(array $farm): array
    {
        if ($farm['location_lat'] !== null && $farm['location_lng'] !== null) {
            return [(float) $farm['location_lat'], (float) $farm['location_lng']];
        }
        $needle = mb_strtolower((string) ($farm['location'] ?? '') . ' ' . (string) ($farm['name'] ?? ''));
        foreach (self::KNOWN_PLACES as $place => $coords) {
            if (str_contains($needle, $place)) {
                return $coords;
            }
        }
        return self::KNOWN_PLACES['lilongwe'];
    }

    /** @return array<string,mixed>|null */
    private function fetch(float $lat, float $lng): ?array
    {
        $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => round($lat, 4), 'longitude' => round($lng, 4),
            'current' => 'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m',
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max',
            'timezone' => 'Africa/Blantyre',
            'forecast_days' => 7,
        ]);

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_USERAGENT => 'AgriVault/1.0',
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $status !== 200) {
                return null;
            }
            $json = json_decode((string) $body, true);
            if (!is_array($json) || !isset($json['current'], $json['daily'])) {
                return null;
            }

            $daily = [];
            $days = count($json['daily']['time'] ?? []);
            for ($i = 0; $i < $days; $i++) {
                $daily[] = [
                    'date' => $json['daily']['time'][$i] ?? null,
                    'code' => $json['daily']['weather_code'][$i] ?? null,
                    'max' => $json['daily']['temperature_2m_max'][$i] ?? null,
                    'min' => $json['daily']['temperature_2m_min'][$i] ?? null,
                    'rain_mm' => $json['daily']['precipitation_sum'][$i] ?? null,
                    'rain_chance' => $json['daily']['precipitation_probability_max'][$i] ?? null,
                ];
            }

            return [
                'current' => [
                    'temp' => $json['current']['temperature_2m'] ?? null,
                    'humidity' => $json['current']['relative_humidity_2m'] ?? null,
                    'precipitation' => $json['current']['precipitation'] ?? null,
                    'code' => $json['current']['weather_code'] ?? null,
                    'wind' => $json['current']['wind_speed_10m'] ?? null,
                ],
                'daily' => $daily,
            ];
        } catch (Throwable $e) {
            Logger::instance()->warning('Weather fetch failed: {msg}', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /** Open-Meteo WMO weather code -> short label. */
    public static function codeLabel(?int $code): string
    {
        return match (true) {
            $code === null => 'Unknown',
            $code === 0 => 'Clear sky',
            in_array($code, [1, 2, 3], true) => 'Partly cloudy',
            in_array($code, [45, 48], true) => 'Fog',
            in_array($code, [51, 53, 55, 56, 57], true) => 'Drizzle',
            in_array($code, [61, 63, 65, 66, 67], true) => 'Rain',
            in_array($code, [71, 73, 75, 77], true) => 'Snow',
            in_array($code, [80, 81, 82], true) => 'Rain showers',
            in_array($code, [85, 86], true) => 'Snow showers',
            in_array($code, [95, 96, 99], true) => 'Thunderstorm',
            default => 'Variable',
        };
    }
}
