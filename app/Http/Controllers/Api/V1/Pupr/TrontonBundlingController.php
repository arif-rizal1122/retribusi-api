<?php

namespace App\Http\Controllers\Api\V1\Pupr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TrontonBundlingController extends Controller
{
    // Koordinat UPTD Workshop Alat Berat Dinas PUPR Kota Baubau (Palagimata)
    const WORKSHOP_LAT = -5.4715;
    const WORKSHOP_LNG = 122.6180;

    // Tarif Dasar Mobilisasi Tronton Self-Loader (Perda 1/2024)
    const BASE_MOBILIZATION_FEE = 1500000; // Rp 1.500.000 (zona dalam kota <= 10 km)
    const RATE_PER_ADDITIONAL_KM = 50000;  // Rp 50.000/km di atas 10 km
    const THRESHOLD_KM = 10.0;

    /**
     * Hitung estimasi biaya bundling mobilisasi tronton berdasarkan koordinat GPS proyek
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_round_trip' => 'nullable|boolean', // Mobilisasi + Demobilisasi
            'asset_item_id' => 'nullable|integer|exists:asset_items,id',
        ]);

        $projectLat = (float) $validated['latitude'];
        $projectLng = (float) $validated['longitude'];
        $isRoundTrip = $validated['is_round_trip'] ?? true;

        // Hitung jarak Haversine (km)
        $distanceKm = $this->calculateHaversine(
            self::WORKSHOP_LAT,
            self::WORKSHOP_LNG,
            $projectLat,
            $projectLng
        );

        // Bulatkan ke 1 desimal
        $distanceKm = round($distanceKm, 1);

        // Hitung tarif one-way
        $oneWayFee = self::BASE_MOBILIZATION_FEE;
        if ($distanceKm > self::THRESHOLD_KM) {
            $extraKm = $distanceKm - self::THRESHOLD_KM;
            $oneWayFee += ($extraKm * self::RATE_PER_ADDITIONAL_KM);
        }

        $totalFee = $isRoundTrip ? ($oneWayFee * 2) : $oneWayFee;

        return response()->json([
            'status' => 'success',
            'data' => [
                'origin' => [
                    'name' => 'Workshop UPTD Alat Berat PUPR Palagimata',
                    'latitude' => self::WORKSHOP_LAT,
                    'longitude' => self::WORKSHOP_LNG,
                ],
                'destination' => [
                    'latitude' => $projectLat,
                    'longitude' => $projectLng,
                ],
                'distance_km' => $distanceKm,
                'is_round_trip' => $isRoundTrip,
                'breakdown' => [
                    'base_zone_fee' => self::BASE_MOBILIZATION_FEE,
                    'threshold_km' => self::THRESHOLD_KM,
                    'extra_km' => max(0, $distanceKm - self::THRESHOLD_KM),
                    'extra_km_fee' => max(0, ($distanceKm - self::THRESHOLD_KM) * self::RATE_PER_ADDITIONAL_KM),
                    'one_way_total' => $oneWayFee,
                    'trip_multiplier' => $isRoundTrip ? 2 : 1,
                ],
                'total_mobilization_fee' => (float) $totalFee,
            ],
        ]);
    }

    /**
     * Hitung jarak jarak lurus lingkaran bumi (Haversine formula)
     */
    private function calculateHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
