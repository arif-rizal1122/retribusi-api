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
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;

        $empty = [
            'province'     => null,
            'regency'      => null,
            'district'     => null,
            'sub_district' => null,
            'zone_id'      => null,
            'zone_name'    => null,
            'display_name' => null,
        ];

        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}&accept-language=id";
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 6,
                    'header'  => "User-Agent: M-PAD-Baubau/1.0 (mpad.baubaukota.go.id)\r\nAccept: application/json\r\n",
                ],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            $data = json_decode((string) $resp, true);

            if (!is_array($data) || empty($data['address'])) {
                return response()->json(['success' => true, 'data' => $empty]);
            }

            $address = $data['address'];
            $district = $address['district']
                ?? $address['county']
                ?? $address['town']
                ?? null;
            $subDistrict = $address['suburb']
                ?? $address['village']
                ?? $address['neighbourhood']
                ?? $address['hamlet']
                ?? null;

            $result = [
                'province'     => $address['state'] ?? $address['province'] ?? null,
                'regency'      => $address['city'] ?? $address['regency'] ?? $address['county'] ?? null,
                'district'     => $district,
                'sub_district' => $subDistrict,
                'zone_id'      => null,
                'zone_name'    => null,
                'display_name' => $data['display_name'] ?? null,
            ];

            // Cari zona retribusi berdasarkan kecamatan/kelurahan (tabel zones)
            if (Schema::hasTable('zones')) {
                $zoneQuery = DB::table('zones');
                $match = null;
                if ($subDistrict) {
                    $match = (clone $zoneQuery)->where('name', 'like', "%{$subDistrict}%")->first();
                }
                if (!$match && $district) {
                    $match = (clone $zoneQuery)->where('name', 'like', "%{$district}%")->first();
                }
                if ($match) {
                    $result['zone_id'] = (int) $match->id;
                    $result['zone_name'] = $match->name;
                }
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            // Jangan pernah 500 — mobile butuh 200 + null untuk fallback manual
            return response()->json(['success' => true, 'data' => $empty]);
        }
    }
}
