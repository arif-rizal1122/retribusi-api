<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationController extends Controller
{
    /**
     * Resolve koordinat → provinsi/kota/kecamatan/kelurahan (reverse geocode).
     * POST /api/location/resolve  body: {latitude, longitude}
     *
     * Sumber: Nominatim OSM (gratis). Kalau gagal → 200 dengan field null
     * (mobile sudah handle fallback ke dropdown manual).
     */
    public function resolve(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;

        $empty = [
            'province' => null,
            'regency' => null,
            'district' => null,
            'sub_district' => null,
            'zone_id' => null,
            'zone_name' => null,
            'display_name' => null,
            'matched' => false,
            'confidence' => 0,
            'source' => null,
        ];

        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&accept-language=id";
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 6,
                    'header' => "User-Agent: M-PAD-Baubau/1.0 (mpad.baubaukota.go.id)\r\nAccept: application/json\r\n",
                ],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $data = json_decode((string) $resp, true);

            if (! is_array($data) || empty($data['address'])) {
                return response()->json(['success' => true, 'data' => $empty]);
            }

            $address = $data['address'];
            $district = $this->cleanRegionLabel($address['district']
                ?? $address['county']
                ?? $address['town']
                ?? null);
            $subDistrict = $this->cleanRegionLabel($address['suburb']
                ?? $address['village']
                ?? $address['neighbourhood']
                ?? $address['hamlet']
                ?? null);

            $result = [
                'province' => $this->cleanRegionLabel($address['state'] ?? $address['province'] ?? null),
                'regency' => $this->cleanRegionLabel($address['city'] ?? $address['regency'] ?? $address['county'] ?? null),
                'district' => $district,
                'sub_district' => $subDistrict,
                'zone_id' => null,
                'zone_name' => null,
                'display_name' => $data['display_name'] ?? null,
                'matched' => false,
                'confidence' => 0,
                'source' => null,
            ];

            if (Schema::hasTable('zones')) {
                $zoneMatch = $this->matchZone($subDistrict, $district);

                if ($zoneMatch !== null) {
                    $result['matched'] = true;
                    $result['confidence'] = $zoneMatch['confidence'];
                    $result['source'] = $zoneMatch['source'];
                    $result['zone_id'] = (int) $zoneMatch['zone']->id;
                    $result['zone_name'] = $zoneMatch['zone']->name;
                }
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            // Jangan pernah 500 — mobile butuh 200 + null untuk fallback manual
            return response()->json(['success' => true, 'data' => $empty]);
        }
    }

    public static function normalizeRegionName(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/[^\pL\pN\s.\/-]+/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/(^|\s)(kelurahan|kel\.?|desa|kecamatan|kec\.?|kabupaten|kab\.?|kota|zona)(?=\s|$)/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    public static function regionSimilarity(?string $needle, ?string $candidate): int
    {
        $left = self::normalizeRegionName($needle);
        $right = self::normalizeRegionName($candidate);

        if ($left === '' || $right === '') {
            return 0;
        }

        if ($left === $right) {
            return 100;
        }

        $leftTokens = preg_split('/\s+/u', $left, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $rightTokens = preg_split('/\s+/u', $right, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sharedTokens = array_intersect($leftTokens, $rightTokens);

        if ($sharedTokens !== []) {
            $coverage = count($sharedTokens) / max(count($leftTokens), 1);
            if ($coverage === 1.0) {
                return 95;
            }
        }

        if ((str_contains($right, $left) || str_contains($left, $right)) && min(strlen($left), strlen($right)) >= 5) {
            return 90;
        }

        $distance = levenshtein($left, $right);
        $maxLength = max(strlen($left), strlen($right));

        if ($maxLength === 0) {
            return 0;
        }

        return max(0, (int) round((1 - ($distance / $maxLength)) * 100));
    }

    private function cleanRegionLabel(?string $value): ?string
    {
        $clean = self::normalizeRegionName($value);

        if ($clean === '') {
            return null;
        }

        return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    private function matchZone(?string $subDistrict, ?string $district): ?array
    {
        $zones = DB::table('zones')->select(['id', 'name'])->get();
        $best = null;

        foreach ([
            'sub_district' => $subDistrict,
            'district' => $district,
        ] as $source => $name) {
            foreach ($zones as $zone) {
                $confidence = self::regionSimilarity($name, $zone->name);

                if ($confidence >= 85 && ($best === null || $confidence > $best['confidence'])) {
                    $best = [
                        'zone' => $zone,
                        'source' => $source,
                        'confidence' => $confidence,
                    ];
                }
            }
        }

        return $best;
    }
}
