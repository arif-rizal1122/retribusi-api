<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categoryId = DB::table('asset_categories')->where('slug', 'bangunan')->value('id');
        if (!$categoryId) {
            $categoryId = DB::table('asset_categories')->insertGetId([
                'name' => 'Bangunan',
                'slug' => 'bangunan',
                'description' => 'Aset Bangunan dan Gedung Daerah',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $perkimOpd = DB::table('opds')->where('id', 11)->orWhere('name', 'LIKE', '%Perumahan%')->first();
        if (!$perkimOpd) {
            $opdId = DB::table('opds')->insertGetId([
                'id' => 11,
                'code' => 'DISPERKIM',
                'name' => 'Dinas Perumahan, Kawasan Permukiman dan Pertanahan',
                'status' => 'approved',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $opdId = $perkimOpd->id;
        }

        $rusunItems = [
            // Kotamara
            [
                'code' => 'RUSUN-KOTAMARA-I',
                'name' => 'Rusun Kotamara Lantai I',
                'complex' => 'kotamara',
                'floor' => 1,
                'tarif' => 250000,
                'lokasi' => 'Kotamara, Baubau',
            ],
            [
                'code' => 'RUSUN-KOTAMARA-II',
                'name' => 'Rusun Kotamara Lantai II',
                'complex' => 'kotamara',
                'floor' => 2,
                'tarif' => 225000,
                'lokasi' => 'Kotamara, Baubau',
            ],
            // Mahasiswa
            [
                'code' => 'RUSUN-MHS-I',
                'name' => 'Rusun Mahasiswa Lantai I',
                'complex' => 'mahasiswa',
                'floor' => 1,
                'tarif' => 350000,
                'lokasi' => 'Baubau',
            ],
            [
                'code' => 'RUSUN-MHS-II',
                'name' => 'Rusun Mahasiswa Lantai II',
                'complex' => 'mahasiswa',
                'floor' => 2,
                'tarif' => 325000,
                'lokasi' => 'Baubau',
            ],
            [
                'code' => 'RUSUN-MHS-III',
                'name' => 'Rusun Mahasiswa Lantai III',
                'complex' => 'mahasiswa',
                'floor' => 3,
                'tarif' => 300000,
                'lokasi' => 'Baubau',
            ],
            [
                'code' => 'RUSUN-MHS-IV',
                'name' => 'Rusun Mahasiswa Lantai IV',
                'complex' => 'mahasiswa',
                'floor' => 4,
                'tarif' => 275000,
                'lokasi' => 'Baubau',
            ],
            // Wameo
            [
                'code' => 'RUSUN-WAMEO-I',
                'name' => 'Rusun Wameo Lantai I',
                'complex' => 'wameo',
                'floor' => 1,
                'tarif' => 200000,
                'lokasi' => 'Wameo, Baubau',
            ],
            [
                'code' => 'RUSUN-WAMEO-II',
                'name' => 'Rusun Wameo Lantai II',
                'complex' => 'wameo',
                'floor' => 2,
                'tarif' => 175000,
                'lokasi' => 'Wameo, Baubau',
            ],
            [
                'code' => 'RUSUN-WAMEO-III',
                'name' => 'Rusun Wameo Lantai III',
                'complex' => 'wameo',
                'floor' => 3,
                'tarif' => 150000,
                'lokasi' => 'Wameo, Baubau',
            ],
            [
                'code' => 'RUSUN-WAMEO-IV',
                'name' => 'Rusun Wameo Lantai IV',
                'complex' => 'wameo',
                'floor' => 4,
                'tarif' => 125000,
                'lokasi' => 'Wameo, Baubau',
            ],
            // ASN
            [
                'code' => 'RUSUN-ASN-I',
                'name' => 'Rusun ASN Lantai I',
                'complex' => 'asn',
                'floor' => 1,
                'tarif' => 350000,
                'lokasi' => 'Baubau',
            ],
            [
                'code' => 'RUSUN-ASN-II',
                'name' => 'Rusun ASN Lantai II',
                'complex' => 'asn',
                'floor' => 2,
                'tarif' => 325000,
                'lokasi' => 'Baubau',
            ],
            [
                'code' => 'RUSUN-ASN-III',
                'name' => 'Rusun ASN Lantai III',
                'complex' => 'asn',
                'floor' => 3,
                'tarif' => 300000,
                'lokasi' => 'Baubau',
            ],
            [
                'code' => 'RUSUN-ASN-IV',
                'name' => 'Rusun ASN Lantai IV',
                'complex' => 'asn',
                'floor' => 4,
                'tarif' => 275000,
                'lokasi' => 'Baubau',
            ],
        ];

        foreach ($rusunItems as $item) {
            $existing = DB::table('asset_items')->where('code', $item['code'])->first();
            if ($existing) {
                DB::table('asset_items')->where('id', $existing->id)->update([
                    'category_id' => $categoryId,
                    'opd_id' => $opdId,
                    'name' => $item['name'],
                    'tarif_default' => $item['tarif'],
                    'satuan_tarif' => 'per bulan',
                    'kondisi' => 'Baik',
                    'status_operasional' => 'Operasional',
                    'lokasi' => $item['lokasi'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
                $assetItemId = $existing->id;
            } else {
                $assetItemId = DB::table('asset_items')->insertGetId([
                    'category_id' => $categoryId,
                    'opd_id' => $opdId,
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'kondisi' => 'Baik',
                    'status_operasional' => 'Operasional',
                    'lokasi' => $item['lokasi'],
                    'pic' => 'Dinas Perkim Kota Baubau',
                    'tarif_default' => $item['tarif'],
                    'satuan_tarif' => 'per bulan',
                    'buffer_default' => 0,
                    'is_demo' => false,
                    'is_active' => true,
                    'metadata' => json_encode(['complex_code' => $item['complex'], 'floor' => $item['floor']]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed 20 rooms per floor (contoh: floor 1 -> 101..120, floor 2 -> 201..220)
            $floorPrefix = $item['floor'] * 100;
            for ($r = 1; $r <= 20; $r++) {
                $roomNo = (string) ($floorPrefix + $r);
                $existingRoom = DB::table('rusun_rooms')
                    ->where('asset_item_id', $assetItemId)
                    ->where('room_number', $roomNo)
                    ->first();

                if (!$existingRoom) {
                    DB::table('rusun_rooms')->insert([
                        'asset_item_id' => $assetItemId,
                        'complex_code' => $item['complex'],
                        'floor' => $item['floor'],
                        'room_number' => $roomNo,
                        'status' => 'available',
                        'notes' => 'Unit kamar ' . $item['name'] . ' No. ' . $roomNo,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $rusunCodes = [
            'RUSUN-KOTAMARA-I', 'RUSUN-KOTAMARA-II',
            'RUSUN-MHS-I', 'RUSUN-MHS-II', 'RUSUN-MHS-III', 'RUSUN-MHS-IV',
            'RUSUN-WAMEO-I', 'RUSUN-WAMEO-II', 'RUSUN-WAMEO-III', 'RUSUN-WAMEO-IV',
            'RUSUN-ASN-I', 'RUSUN-ASN-II', 'RUSUN-ASN-III', 'RUSUN-ASN-IV',
        ];

        $assetItemIds = DB::table('asset_items')->whereIn('code', $rusunCodes)->pluck('id');

        DB::table('rusun_rooms')->whereIn('asset_item_id', $assetItemIds)->delete();

        // Hapus item yang khusus dibuat di migrasi ini (Kotamara dan lantai tambahan)
        $newCodes = [
            'RUSUN-KOTAMARA-I', 'RUSUN-KOTAMARA-II',
            'RUSUN-MHS-II', 'RUSUN-MHS-III',
            'RUSUN-WAMEO-II', 'RUSUN-WAMEO-III', 'RUSUN-WAMEO-IV',
            'RUSUN-ASN-II', 'RUSUN-ASN-III', 'RUSUN-ASN-IV',
        ];
        DB::table('asset_items')->whereIn('code', $newCodes)->delete();
    }
};
