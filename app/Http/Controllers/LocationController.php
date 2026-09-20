<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationController extends Controller
{
    public const BAUBAU_DATA = [
        'Batupoaro' => ['Bone-bone', 'Tarafu', 'Wameo', 'Kaobula', 'Lanto', 'Ngananaumala'],
        'Betoambari' => ['Sulaa', 'Waborobo', 'Labalawa', 'Lipu', 'Katobengke'],
        'Bungi' => ['Bugi', 'Gonda Baru', 'Kaisabu Baru', 'Karya Baru', 'Ngkari-Ngkari'],
        'Kokalukuna' => ['Kadolomoko', 'Waruruma', 'Lakologou', 'Kadolo', 'Liwuto', 'Sukanaeyo'],
        'Lea-lea' => ['Palabusa', 'Kalia-Lia', 'Kantalai', 'Kolese', 'Lowu-Lowu'],
        'Murhum' => ['Baadia', 'Melai', 'Wajo', 'Lamangga', 'Tanganapada'],
        'Sorawolio' => ['Gonda Baru', 'Karya Baru', 'Bugis', 'Gonda'],
        'Wolio' => ['Bataraguru', 'Tomba', 'Wangkanapi', 'Wale', 'Batulo', 'Bukit Wolio Indah', 'Kadolokatapi'],
    ];

    public const DISTRICT_CENTERS = [
        'Batupoaro' => [-5.4660, 122.5970],
        'Wolio' => [-5.4628, 122.6075],
        'Murhum' => [-5.4740, 122.5990],
        'Betoambari' => [-5.4950, 122.5850],
        'Kokalukuna' => [-5.4420, 122.6350],
        'Bungi' => [-5.4200, 122.6700],
        'Sorawolio' => [-5.5100, 122.6600],
        'Lea-lea' => [-5.3900, 122.6400],
    ];

    public const SUBDISTRICT_ALIASES = [
        'kanakea' => ['Batupoaro', 'Ngananaumala'],
        'kanakea dalam' => ['Batupoaro', 'Ngananaumala'],
        'kanakea luar' => ['Batupoaro', 'Ngananaumala'],
        'nganganaumala' => ['Batupoaro', 'Ngananaumala'],
        'kaobobu' => ['Batupoaro', 'Kaobula'],
        'kotamara' => ['Batupoaro', 'Kaobula'],
        'keraton' => ['Murhum', 'Melai'],
        'benteng keraton' => ['Murhum', 'Melai'],
        'pantai kamali' => ['Wolio', 'Batulo'],
        'bandara' => ['Betoambari', 'Katobengke'],
        'bandara betoambari' => ['Betoambari', 'Katobengke'],
        'nirwana' => ['Betoambari', 'Sulaa'],
        'pantai nirwana' => ['Betoambari', 'Sulaa'],
        'lakeba' => ['Betoambari', 'Katobengke'],
        'bwi' => ['Wolio', 'Bukit Wolio Indah'],
        'ngkaringkari' => ['Bungi', 'Ngkari-Ngkari'],
        'kalia lia' => ['Lea-lea', 'Kalia-Lia'],
        'lowu lowu' => ['Lea-lea', 'Lowu-Lowu'],
        'lea lea' => ['Lea-lea', 'Lowu-Lowu'],
        'kantola' => ['Lea-lea', 'Kantalai'],
    ];

    /**
     * Resolve koordinat → provinsi/kota/kecamatan/kelurahan (reverse geocode).
     * POST /api/location/resolve  body: {latitude, longitude}
     */
    public function resolve(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;

        $displayName = null;
        $district = null;
        $subDistrict = null;
        $province = 'Sulawesi Tenggara';
        $regency = 'Kota Baubau';

        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&accept-language=id";
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'header' => "User-Agent: M-PAD-Baubau/1.0 (mpad.baubaukota.go.id)\r\nAccept: application/json\r\n",
                ],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $data = json_decode((string) $resp, true);

            if (is_array($data)) {
                $displayName = $data['display_name'] ?? null;
                $address = $data['address'] ?? [];

                // Kumpulkan seluruh teks deskriptif untuk scanning
                $inspectText = strtolower(trim(
                    ($displayName ?? '').' '.
                    ($data['name'] ?? '').' '.
                    ($address['village'] ?? '').' '.
                    ($address['suburb'] ?? '').' '.
                    ($address['neighbourhood'] ?? '').' '.
                    ($address['hamlet'] ?? '').' '.
                    ($address['district'] ?? '').' '.
                    ($address['county'] ?? '').' '.
                    ($address['road'] ?? '')
                ));

                // 1. Cek alias lingkungan Baubau
                foreach (self::SUBDISTRICT_ALIASES as $alias => $target) {
                    if (str_contains($inspectText, $alias)) {
                        $district = $target[0];
                        $subDistrict = $target[1];
                        break;
                    }
                }

                // 2. Cek Kelurahan di seluruh BAUBAU_DATA
                if ($subDistrict === null) {
                    foreach (self::BAUBAU_DATA as $kec => $kels) {
                        foreach ($kels as $kel) {
                            $cleanKel = strtolower(preg_replace('/[^a-z0-9]/', '', $kel));
                            $cleanInspect = preg_replace('/[^a-z0-9]/', '', $inspectText);
                            if (str_contains($cleanInspect, $cleanKel)) {
                                $district = $kec;
                                $subDistrict = $kel;
                                break 2;
                            }
                        }
                    }
                }

                // 3. Cek Kecamatan jika kelurahan belum ketemu
                if ($district === null) {
                    foreach (self::BAUBAU_DATA as $kec => $kels) {
                        $cleanKec = strtolower(preg_replace('/[^a-z0-9]/', '', $kec));
                        $cleanInspect = preg_replace('/[^a-z0-9]/', '', $inspectText);
                        if (str_contains($cleanInspect, $cleanKec)) {
                            $district = $kec;
                            $subDistrict = $kels[0] ?? null;
                            break;
                        }
                    }
                }

                if (!empty($address['state']) || !empty($address['province'])) {
                    $province = $this->cleanRegionLabel($address['state'] ?? $address['province']) ?? $province;
                }
                if (!empty($address['city']) || !empty($address['regency'])) {
                    $regency = $this->cleanRegionLabel($address['city'] ?? $address['regency']) ?? $regency;
                }
            }
        } catch (\Throwable $e) {
            // Lanjut ke fallback spasial
        }

        // 4. Fallback Spasial Baubau (Centroid) bila koordinat berada di sekitar Baubau
        // Range Baubau: lat -5.65 sd -5.30, lng 122.50 sd 122.80
        if (($district === null || $subDistrict === null) && $lat >= -5.65 && $lat <= -5.30 && $lng >= 122.50 && $lng <= 122.80) {
            $minDist = INF;
            $bestKec = 'Batupoaro';
            foreach (self::DISTRICT_CENTERS as $kec => $center) {
                $dLat = $lat - $center[0];
                $dLng = $lng - $center[1];
                $dist = ($dLat * $dLat) + ($dLng * $dLng);
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $bestKec = $kec;
                }
            }
            if ($district === null) {
                $district = $bestKec;
            }
            if ($subDistrict === null) {
                $subDistrict = self::BAUBAU_DATA[$district][0] ?? 'Wameo';
            }
        }

        // Format display name bila belum terisi
        if (!$displayName && $district && $subDistrict) {
            $displayName = "{$subDistrict}, {$district}, {$regency}, {$province}";
        }

        $result = [
            'province' => $province,
            'regency' => $regency,
            'district' => $district,
            'sub_district' => $subDistrict,
            'zone_id' => null,
            'zone_name' => null,
            'display_name' => $displayName,
            'matched' => ($district !== null && $subDistrict !== null),
            'confidence' => ($district !== null && $subDistrict !== null) ? 95 : 0,
            'source' => 'mpad_spatial',
        ];

        if (Schema::hasTable('zones') && ($district || $subDistrict)) {
            $zoneMatch = $this->matchZone($subDistrict, $district);
            if ($zoneMatch !== null) {
                $result['zone_id'] = (int) $zoneMatch['zone']->id;
                $result['zone_name'] = $zoneMatch['zone']->name;
            }
        }

        return response()->json(['success' => true, 'data' => $result]);
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
