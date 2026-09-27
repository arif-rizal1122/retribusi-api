<?php

namespace App\Support;

use App\Models\GeomActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SpatialAuditLogger
{
    /**
     * Catat audit log aktivitas geospasial & klaim NOP sesuai UU PDP No. 27/2022.
     */
    public static function log(
        string $action,
        ?string $nop = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $deviceLat = null,
        ?float $deviceLng = null,
        ?float $distanceKm = null,
        bool $isFlaggedRemote = false,
        array $details = []
    ): void {
        try {
            $user = auth()->user() ?? auth('sanctum')->user();
            $userType = $user ? get_class($user) : null;
            $userId = $user ? $user->id : null;

            $request = request();
            $ip = $request ? $request->ip() : null;
            $ua = $request ? substr((string) $request->userAgent(), 0, 500) : null;

            if (Schema::hasTable('geom_activity_logs')) {
                GeomActivityLog::create([
                    'user_type' => $userType,
                    'user_id' => $userId,
                    'action' => $action,
                    'nop' => $nop,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'device_lat' => $deviceLat,
                    'device_lng' => $deviceLng,
                    'distance_km' => $distanceKm,
                    'is_flagged_remote' => $isFlaggedRemote,
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'details' => $details,
                    'created_at' => now(),
                ]);
            } else {
                Log::info('SpatialAuditLogger (fallback file log)', [
                    'action' => $action,
                    'nop' => $nop ? NopHelper::mask($nop) : null,
                    'user_id' => $userId,
                    'is_flagged_remote' => $isFlaggedRemote,
                    'distance_km' => $distanceKm,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SpatialAuditLogger error: '.$e->getMessage());
        }
    }

    /**
     * Hitung jarak Haversine antara dua titik koordinat (dalam kilometer).
     */
    public static function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
