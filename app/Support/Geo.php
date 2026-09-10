<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight geodesy for field polygons. Accuracy is field-scale (well under
 * 1% for parcels a few km across), which is all the record-keeping needs — no
 * spatial database extension required on the host.
 */
final class Geo
{
    private const EARTH_RADIUS_M = 6_378_137.0;

    /**
     * Area in hectares of a GeoJSON Polygon/MultiPolygon or a bare ring
     * (list of [lng,lat] pairs). Uses an equirectangular projection about the
     * ring's mean latitude, then the shoelace formula.
     *
     * @param array<mixed> $geoJson
     */
    public static function areaHa(array $geoJson): ?float
    {
        $rings = self::extractRings($geoJson);
        if ($rings === []) {
            return null;
        }

        $total = 0.0;
        foreach ($rings as $i => $ring) {
            $a = self::ringAreaM2($ring);
            // First ring is the outer boundary; any others are holes.
            $total += $i === 0 ? $a : -$a;
        }
        return $total > 0 ? round($total / 10_000, 4) : null;
    }

    /**
     * Centroid [lat, lng] of the outer ring, or null.
     *
     * @param array<mixed> $geoJson
     * @return array{0:float,1:float}|null
     */
    public static function centroid(array $geoJson): ?array
    {
        $rings = self::extractRings($geoJson);
        if ($rings === [] || count($rings[0]) < 3) {
            return null;
        }
        $ring = $rings[0];
        $sumLat = 0.0;
        $sumLng = 0.0;
        $n = 0;
        foreach ($ring as [$lng, $lat]) {
            $sumLat += (float) $lat;
            $sumLng += (float) $lng;
            $n++;
        }
        return $n > 0 ? [round($sumLat / $n, 6), round($sumLng / $n, 6)] : null;
    }

    /**
     * @param array<mixed> $geoJson
     * @return list<list<array{0:float,1:float}>>
     */
    private static function extractRings(array $geoJson): array
    {
        // Accept a Feature, a Geometry, or a bare coordinate ring.
        $geom = $geoJson['geometry'] ?? $geoJson;
        $type = $geom['type'] ?? null;
        $coords = $geom['coordinates'] ?? $geoJson;

        if ($type === 'Polygon' && is_array($coords)) {
            return array_values(array_map(self::normaliseRing(...), $coords));
        }
        if ($type === 'MultiPolygon' && is_array($coords)) {
            $out = [];
            foreach ($coords as $poly) {
                foreach ((array) $poly as $ring) {
                    $out[] = self::normaliseRing($ring);
                }
            }
            return $out;
        }
        // Bare ring: [[lng,lat], ...]
        if (is_array($coords) && isset($coords[0]) && is_array($coords[0]) && is_numeric($coords[0][0] ?? null)) {
            return [self::normaliseRing($coords)];
        }
        return [];
    }

    /**
     * @param array<mixed> $ring
     * @return list<array{0:float,1:float}>
     */
    private static function normaliseRing(array $ring): array
    {
        $out = [];
        foreach ($ring as $pt) {
            if (is_array($pt) && isset($pt[0], $pt[1]) && is_numeric($pt[0]) && is_numeric($pt[1])) {
                $out[] = [(float) $pt[0], (float) $pt[1]];
            }
        }
        return $out;
    }

    /** @param list<array{0:float,1:float}> $ring [lng,lat] pairs */
    private static function ringAreaM2(array $ring): float
    {
        $n = count($ring);
        if ($n < 3) {
            return 0.0;
        }
        $meanLatRad = 0.0;
        foreach ($ring as [, $lat]) {
            $meanLatRad += deg2rad($lat);
        }
        $meanLatRad /= $n;
        $mPerDegLat = (M_PI / 180) * self::EARTH_RADIUS_M;
        $mPerDegLng = $mPerDegLat * cos($meanLatRad);

        $area = 0.0;
        for ($i = 0; $i < $n; $i++) {
            [$lng1, $lat1] = $ring[$i];
            [$lng2, $lat2] = $ring[($i + 1) % $n];
            $x1 = $lng1 * $mPerDegLng;
            $y1 = $lat1 * $mPerDegLat;
            $x2 = $lng2 * $mPerDegLng;
            $y2 = $lat2 * $mPerDegLat;
            $area += ($x1 * $y2) - ($x2 * $y1);
        }
        return abs($area) / 2.0;
    }
}
