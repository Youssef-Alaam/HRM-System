<?php

namespace App\Support;

/**
 * Great-circle distance between two lat/long points in metres.
 * Used by AttendanceService to validate check-in proximity to an office.
 * Pure function — no DB, no side effects.
 */
class Haversine
{
    private const EARTH_RADIUS_METRES = 6_371_000;

    /**
     * Distance in metres between (lat1, lng1) and (lat2, lng2).
     * Decimal degrees, WGS84 sphere approximation. Accuracy is well within
     * ±0.5% over distances < 1000 km, which is more than enough for office
     * proximity checks.
     */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLambda = deg2rad($lng2 - $lng1);

        $a = sin($dPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($dLambda / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METRES * $c;
    }
}
