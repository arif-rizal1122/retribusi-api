<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Seed & ensure all 24 heavy equipment, tools, vehicles, and tronton tiers
     * from Perda Kota Baubau No. 1 Tahun 2024 Lampiran II are registered in asset_items.
     */
    public function up(): void
    {
        $puprOpd = DB::table('opds')->where('id', 9)->orWhere('name', 'LIKE', '%Pekerjaan Umum%')->first();
        if (!$puprOpd) {
            $opdId = DB::table('opds')->insertGetId([
                'id' => 9,
                'name' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
                'code' => 'PUPR',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $opdId = $puprOpd->id;
        }

        // Category IDs: 3 = Alat Berat, 4 = Kendaraan
        $catAlatBerat = DB::table('asset_categories')->where('slug', 'alat-berat')->value('id');
        if (!$catAlatBerat) {
            $catAlatBerat = DB::table('asset_categories')->insertGetId([
                'id' => 3,
                'name' => 'Alat Berat & Peralatan Konstruksi',
                'slug' => 'alat-berat',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $catKendaraan = DB::table('asset_categories')->where('slug', 'kendaraan')->value('id');
        if (!$catKendaraan) {
            $catKendaraan = DB::table('asset_categories')->insertGetId([
                'id' => 4,
                'name' => 'Kendaraan & Angkutan',
                'slug' => 'kendaraan',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $items = [
            // --- ALAT BERAT & PERALATAN KONSTRUKSI (PERDA 1/2024) ---
            [
                'code' => 'AB-001',
                'category_id' => $catAlatBerat,
                'name' => 'Excavator 138 HP/Komatsu PC200-8M0',
                'merk_type' => 'Komatsu PC200-8M0',
                'spesifikasi' => '138 HP / Bucket 0.8 m3 / Bobot 20 Ton',
                'tarif_default' => 330000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => true,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-002',
                'category_id' => $catAlatBerat,
                'name' => 'Excavator 138 HP/Komatsu PC200-8M0 + Breaker Komatsu JTHB 210',
                'merk_type' => 'Komatsu PC200-8M0 + JTHB 210',
                'spesifikasi' => '138 HP + Hydraulic Breaker Penghancur Batu',
                'tarif_default' => 360000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => true,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-003',
                'category_id' => $catAlatBerat,
                'name' => 'Wheel Loader 123 HP/Komatsu WA200-5',
                'merk_type' => 'Komatsu WA200-5',
                'spesifikasi' => '123 HP / Bucket 2.0 m3 / Roda Karet',
                'tarif_default' => 250000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-004',
                'category_id' => $catAlatBerat,
                'name' => 'Tandem Roller 28,4 HP/4T/Bomag BW131AD-3',
                'merk_type' => 'Bomag BW131AD-3',
                'spesifikasi' => '28,4 HP / Bobot 4 Ton / Pemadat Aspal',
                'tarif_default' => 170000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => true,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-005',
                'category_id' => $catAlatBerat,
                'name' => 'Bulldozer 80 HP/Komatsu D31Ex',
                'merk_type' => 'Komatsu D31Ex',
                'spesifikasi' => '80 HP / Rantai Track / Pisau Dorong Lahan',
                'tarif_default' => 300000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => true,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-006',
                'category_id' => $catAlatBerat,
                'name' => 'Backhoe Loader 92 HP/Caterpillar CAT 416F2',
                'merk_type' => 'Caterpillar CAT 416F2',
                'spesifikasi' => '92 HP / Kombinasi Loader Depan & Backhoe Belakang',
                'tarif_default' => 250000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-007',
                'category_id' => $catAlatBerat,
                'name' => 'Motor Grader 135 HP/Komatsu GD511A-1',
                'merk_type' => 'Komatsu GD511A-1',
                'spesifikasi' => '135 HP / Blade 3.71m / Perata Badan Jalan',
                'tarif_default' => 350000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-008',
                'category_id' => $catAlatBerat,
                'name' => 'Single Drum Rollers 132 HP/13T/Bomag BW211D',
                'merk_type' => 'Bomag BW211D',
                'spesifikasi' => '132 HP / Bobot 13 Ton / Pemadat Tanah Vibratory Roller',
                'tarif_default' => 250000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => true,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'AB-009',
                'category_id' => $catAlatBerat,
                'name' => 'Tandem Roller 74 HP/7,6T/Bomag BW151AD',
                'merk_type' => 'Bomag BW151AD',
                'spesifikasi' => '74 HP / Bobot 7,6 Ton / Pemadat Aspal Hotmix Berat',
                'tarif_default' => 250000.00,
                'satuan_tarif' => 'per jam',
                'wajib_tronton' => true,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat PUPR Baubau',
            ],
            [
                'code' => 'PK-001',
                'category_id' => $catAlatBerat,
                'name' => 'Concrete Cutter',
                'merk_type' => 'Mikasa / Honda GX390',
                'spesifikasi' => 'Pemotong Beton & Aspal Jalan / Diamond Blade 14-16 inch',
                'tarif_default' => 250000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Peralatan PUPR Baubau',
            ],
            [
                'code' => 'PK-002',
                'category_id' => $catAlatBerat,
                'name' => 'Jack Hammer',
                'merk_type' => 'Atlas Copco / Toku',
                'spesifikasi' => 'Pneumatic / Electric Breaker Bobok Beton & Pondasi',
                'tarif_default' => 250000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Peralatan PUPR Baubau',
            ],
            [
                'code' => 'PU-001',
                'category_id' => $catAlatBerat,
                'name' => 'Digital Theodolit',
                'merk_type' => 'Topcon / Sokkia DT-Series',
                'spesifikasi' => 'Alat Ukur Sudut & Pemetaan Topografi Digital',
                'tarif_default' => 150000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Peralatan PUPR Baubau',
            ],
            [
                'code' => 'PU-002',
                'category_id' => $catAlatBerat,
                'name' => 'Automatic Level Waterpass',
                'merk_type' => 'Topcon AT-B4A',
                'spesifikasi' => 'Alat Ukur Ketinggian & Elevasi Tanah Konstruksi',
                'tarif_default' => 150000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Peralatan PUPR Baubau',
            ],
            [
                'code' => 'PK-003',
                'category_id' => $catAlatBerat,
                'name' => 'Asphalt Sprayer',
                'merk_type' => 'Asphalt Sprayer 1000L',
                'spesifikasi' => 'Penyemprot Aspal Emulsi / Prime Coat / Tack Coat Jalan',
                'tarif_default' => 300000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Peralatan PUPR Baubau',
            ],

            // --- KENDARAAN, TRUK & TRONTON (PERDA 1/2024) ---
            [
                'code' => 'KN-001',
                'category_id' => $catKendaraan,
                'name' => 'Dump Truck Toyota Dyna 110',
                'merk_type' => 'Toyota Dyna 110',
                'spesifikasi' => 'Kapasitas Bak 4 - 5 m3 / Daya 110 PS',
                'tarif_default' => 350000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'KN-002',
                'category_id' => $catKendaraan,
                'name' => 'Dump Truck Toyota Dyna 130',
                'merk_type' => 'Toyota Dyna 130',
                'spesifikasi' => 'Kapasitas Bak 6 - 8 m3 / Daya 130 PS',
                'tarif_default' => 400000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'KN-003',
                'category_id' => $catKendaraan,
                'name' => 'Flat Deck Truck Toyota Dyna 110',
                'merk_type' => 'Toyota Dyna 110 Flat Deck',
                'spesifikasi' => 'Truk Bak Datar Pengangkut Material & Alat',
                'tarif_default' => 350000.00,
                'satuan_tarif' => 'per hari',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'TR-001',
                'category_id' => $catKendaraan,
                'name' => 'Self Loader Truck/Trailer/Tronton (0-20 Km)',
                'merk_type' => 'Self Loader Tronton',
                'spesifikasi' => 'Truk Angkut Tronton Khusus Alat Berat Jarak 0 - 20 Km',
                'tarif_default' => 450000.00,
                'satuan_tarif' => 'per trip',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'TR-002',
                'category_id' => $catKendaraan,
                'name' => 'Self Loader Truck/Trailer/Tronton (20-40 Km)',
                'merk_type' => 'Self Loader Tronton',
                'spesifikasi' => 'Truk Angkut Tronton Khusus Alat Berat Jarak 20 - 40 Km',
                'tarif_default' => 900000.00,
                'satuan_tarif' => 'per trip',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'TR-003',
                'category_id' => $catKendaraan,
                'name' => 'Self Loader Truck/Trailer/Tronton (40-60 Km)',
                'merk_type' => 'Self Loader Tronton',
                'spesifikasi' => 'Truk Angkut Tronton Khusus Alat Berat Jarak 40 - 60 Km',
                'tarif_default' => 1350000.00,
                'satuan_tarif' => 'per trip',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'TR-004',
                'category_id' => $catKendaraan,
                'name' => 'Self Loader Truck/Trailer/Tronton (60-80 Km)',
                'merk_type' => 'Self Loader Tronton',
                'spesifikasi' => 'Truk Angkut Tronton Khusus Alat Berat Jarak 60 - 80 Km',
                'tarif_default' => 1800000.00,
                'satuan_tarif' => 'per trip',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'TR-005',
                'category_id' => $catKendaraan,
                'name' => 'Self Loader Truck/Trailer/Tronton (80-100 Km)',
                'merk_type' => 'Self Loader Tronton',
                'spesifikasi' => 'Truk Angkut Tronton Khusus Alat Berat Jarak 80 - 100 Km',
                'tarif_default' => 2700000.00,
                'satuan_tarif' => 'per trip',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'TR-006',
                'category_id' => $catKendaraan,
                'name' => 'Self Loader Truck/Trailer/Tronton (Lebih dari 100 Km)',
                'merk_type' => 'Self Loader Tronton',
                'spesifikasi' => 'Truk Angkut Tronton Khusus Alat Berat Jarak Luar Kota (> 100 Km)',
                'tarif_default' => 2250000.00,
                'satuan_tarif' => 'per trip',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'Pool Kendaraan PUPR Baubau',
            ],
            [
                'code' => 'KN-006',
                'category_id' => $catKendaraan,
                'name' => 'Mobil Sedot Tinja (Armada Khusus Sedot Kakus)',
                'merk_type' => 'Sedot Tinja DT 8038 G',
                'spesifikasi' => 'Truk Tangki Vakum Sedot Tinja / Kakus Kapasitas 3000 Liter',
                'tarif_default' => 150000.00,
                'satuan_tarif' => 'per rit',
                'wajib_tronton' => false,
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPT PUPR Baubau',
            ],
        ];

        foreach ($items as $item) {
            $existing = DB::table('asset_items')
                ->where('code', $item['code'])
                ->orWhere('name', $item['name'])
                ->first();

            $payload = [
                'category_id' => $item['category_id'],
                'opd_id' => $opdId,
                'name' => $item['name'],
                'code' => $item['code'],
                'merk_type' => $item['merk_type'],
                'spesifikasi' => $item['spesifikasi'],
                'kondisi' => $item['kondisi'],
                'status_operasional' => $item['status_operasional'],
                'lokasi' => $item['lokasi'],
                'pic' => 'UPTD Workshop & Alat Berat PUPR',
                'tarif_default' => $item['tarif_default'],
                'satuan_tarif' => $item['satuan_tarif'],
                'wajib_tronton' => $item['wajib_tronton'],
                'buffer_default' => 1,
                'is_demo' => false,
                'is_active' => true,
                'metadata' => json_encode([
                    'legal_basis' => 'Perda Kota Baubau No. 1 Tahun 2024 Lampiran II',
                    'updated_at' => now()->toIso8601String(),
                ]),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('asset_items')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('asset_items')->insert($payload);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep records for data integrity
    }
};
