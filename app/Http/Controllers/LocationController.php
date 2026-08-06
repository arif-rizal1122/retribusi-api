<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Zone;

class LocationController extends Controller
{
    /**
     * Resolve koordinat (lat, lng) menjadi wilayah administrasi (kecamatan/kelurahan)
     * via Nominatim reverse geocoding, plus deteksi zona M-PAD (point-in-polygon).
     *
     * Body: { latitude: -5.xxxx, longitude: 122.xxxx }
     */
    public function resolve(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;

        // 1) Reverse geocode via Nominatim (OSM) — gratis, no API key
        $region = $this->reverseGeocode($lat, $lng);

        // 2) Point-in-polygon terhadap zona M-PAD (data sendiri)
        $zone = $this->detectZone($lat, $lng);

        return response()->json([
            'data' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'province' => $region['province'] ?? null,
                'regency' => $region['regency'] ?? null,
                'district' => $region['district'] ?? null,
                'sub_district' => $region['sub_district'] ?? null,
                'display_name' => $region['display_name'] ?? null,
                'zone_id' => $zone['id'] ?? null,
                'zone_name' => $zone['name'] ?? null,
                'zone_code' => $zone['code'] ?? null,
                'source' => 'nominatim+mpad',
            ],
        ]);
    }

    /**
     * Reverse geocode via Nominatim (public OSM). Batasi ke Indonesia untuk
     * mengurangi noise. Fallback: null jika gagal/rate-limited.
     */
    private function reverseGeocode(float $lat, float $lng): array
    {
        try {
            $resp = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'MPAD-Baubau/1.0 (contact: bapenda@baubaukota.go.id)'])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'jsonv2',
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if (! $resp->ok()) {
                return [];
            }

            $data = $resp->json();
            $addr = $data['address'] ?? [];

            $result = [
                'province' => $addr['province'] ?? $addr['state'] ?? null,
                'regency' => $addr['county'] ?? $addr['city'] ?? $addr['municipality'] ?? null,
                'district' => $addr['district'] ?? $addr['county'] ?? null,
                'sub_district' => $addr['village'] ?? $addr['suburb'] ?? $addr['neighbourhood'] ?? null,
                'display_name' => $data['display_name'] ?? null,
            ];

            // Fallback kecamatan: cocokkan kelurahan hasil Nominatim ke
            // regional_areas M-PAD, lalu ambil parent (kecamatan).
            if (empty($result['district']) && ! empty($result['sub_district'])) {
                $village = \App\Models\RegionalArea::query()
                    ->where('level', 'village')
                    ->where(function ($q) use ($result) {
                        $q->where('name', 'like', '%' . $result['sub_district'] . '%')
                          ->orWhereRaw('? LIKE CONCAT("%", name, "%")', [$result['sub_district']]);
                    })
                    ->first();

                if ($village && ! empty($village->parent_code)) {
                    $district = \App\Models\RegionalArea::query()
                        ->where('level', 'district')
                        ->where('code', $village->parent_code)
                        ->first();
                    if ($district) {
                        $result['district'] = $district->name;
                    }
                }
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Point-in-polygon (ray casting) terhadap zona M-PAD yang bertipe polygon.
     * Zona point (titik tunggal) dicocokkan via jarak terdekat.
     */
    private function detectZone(float $lat, float $lng): ?array
    {
        try {
            $zones = Zone::where('geometry_type', 'polygon')->get();
            foreach ($zones as $zone) {
                $coords = $zone->coordinates;
                if (! is_array($coords) || count($coords) < 3) {
                    continue;
                }
                if ($this->pointInPolygon($lat, $lng, $coords)) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'code' => $zone->code,
                    ];
                }
            }

            // Fallback: zona point — cari jarak terdekat (threshold 200m)
            $pointZones = Zone::where('geometry_type', 'point')->get();
            $nearest = null;
            $nearestDist = 200; // meter
            foreach ($pointZones as $zone) {
                if ($zone->latitude === null || $zone->longitude === null) {
                    continue;
                }
                $dist = $this->haversine($lat, $lng, (float) $zone->latitude, (float) $zone->longitude);
                if ($dist <= $nearestDist) {
                    $nearestDist = $dist;
                    $nearest = [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'code' => $zone->code,
                    ];
                }
            }

            return $nearest;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ray casting algorithm — cek apakah titik (lat,lng) berada di dalam polygon.
     * Polygon format: [ [lat, lng], [lat, lng], ... ] (setidaknya 3 titik).
     */
    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float) $polygon[$i][0];
            $yi = (float) $polygon[$i][1];
            $xj = (float) $polygon[$j][0];
            $yj = (float) $polygon[$j][1];

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Jarak haversine dalam meter antara dua koordinat.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
