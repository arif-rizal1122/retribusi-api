<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetModuleDemoSeeder extends Seeder
{
    /**
     * Seed placeholder (is_demo=1) — data TARIF & STRUKTUR mengikuti PERDA 1/2024
     * dan Matriks stakeholder. Data ini real-time (query langsung ke DB),
     * ditandai is_demo agar mudah dibersihkan saat data riil masuk.
     */
    public function run(): void
    {
        // --- KATEGORI (keputusan opsi 2 Pak Massad: 1 layanan, field pertama kategori) ---
        $categories = [
            ['name' => 'Tanah', 'slug' => 'tanah', 'icon' => 'land', 'sort_order' => 1],
            ['name' => 'Bangunan', 'slug' => 'bangunan', 'icon' => 'building', 'sort_order' => 2],
            ['name' => 'Alat Berat', 'slug' => 'alat-berat', 'icon' => 'excavator', 'sort_order' => 3],
            ['name' => 'Kendaraan', 'slug' => 'kendaraan', 'icon' => 'truck', 'sort_order' => 4],
            ['name' => 'Mesin', 'slug' => 'mesin', 'icon' => 'engine', 'sort_order' => 5],
            ['name' => 'Peralatan', 'slug' => 'peralatan', 'icon' => 'tool', 'sort_order' => 6],
        ];
        foreach ($categories as $c) {
            DB::table('asset_categories')->updateOrInsert(
                ['slug' => $c['slug']],
                array_merge($c, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Ambil ID kategori
        $cat = [];
        foreach ($categories as $c) {
            $cat[$c['slug']] = DB::table('asset_categories')->where('slug', $c['slug'])->value('id');
        }

        // --- ITEM PLACEHOLDER (tarif PERDA 1/2024) ---
        $items = [
            // Bangunan — Gedung Serba Guna
            ['cat' => 'bangunan', 'name' => 'Gedung Serba Guna Pancasila', 'code' => 'GSG-001', 'tarif' => 5000000, 'satuan' => 'per kegiatan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            ['cat' => 'bangunan', 'name' => 'Gedung Serba Guna Maedani', 'code' => 'GSG-002', 'tarif' => 5000000, 'satuan' => 'per kegiatan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            ['cat' => 'bangunan', 'name' => 'Gedung Serba Guna Islamic Center', 'code' => 'GSG-003', 'tarif' => 5000000, 'satuan' => 'per kegiatan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            ['cat' => 'bangunan', 'name' => 'Gedung Serba Guna Wa Ode Wau', 'code' => 'GSG-004', 'tarif' => 2500000, 'satuan' => 'per kegiatan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            // Bangunan — Rumah Susun
            ['cat' => 'bangunan', 'name' => 'Rusun Mahasiswa Lantai I', 'code' => 'RUSUN-MHS-I', 'tarif' => 350000, 'satuan' => 'per bulan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            ['cat' => 'bangunan', 'name' => 'Rusun Mahasiswa Lantai IV', 'code' => 'RUSUN-MHS-IV', 'tarif' => 275000, 'satuan' => 'per bulan', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'Baubau'],
            ['cat' => 'bangunan', 'name' => 'Rusun Wameo Lantai I', 'code' => 'RUSUN-WAMEO-I', 'tarif' => 200000, 'satuan' => 'per bulan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            ['cat' => 'bangunan', 'name' => 'Rusun ASN Lantai I', 'code' => 'RUSUN-ASN-I', 'tarif' => 350000, 'satuan' => 'per bulan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Baubau'],
            // Tanah — Lahan Pelabuhan
            ['cat' => 'tanah', 'name' => 'Lahan Pelabuhan - Kantor', 'code' => 'LAHAN-PLB-01', 'tarif' => 200000, 'satuan' => 'per bulan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Pelabuhan Baubau'],
            ['cat' => 'tanah', 'name' => 'Lahan Pelabuhan - Toko', 'code' => 'LAHAN-PLB-02', 'tarif' => 150000, 'satuan' => 'per bulan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Pelabuhan Baubau'],
            ['cat' => 'tanah', 'name' => 'Lahan Pelabuhan - Kios/Petak', 'code' => 'LAHAN-PLB-03', 'tarif' => 120000, 'satuan' => 'per bulan', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'Pelabuhan Baubau'],
            ['cat' => 'tanah', 'name' => 'Lahan Pelabuhan - Los', 'code' => 'LAHAN-PLB-04', 'tarif' => 75000, 'satuan' => 'per bulan', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'Pelabuhan Baubau'],
            // Alat Berat — PUPR (tarif PERDA 1/2024)
            ['cat' => 'alat-berat', 'name' => 'Excavator Komatsu PC200-8M0', 'code' => 'AB-001', 'merk' => 'Komatsu PC200-8M0', 'spes' => '138 HP', 'tarif' => 330000, 'satuan' => 'per jam', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'alat-berat', 'name' => 'Excavator + Breaker Komatsu PC200-8M0', 'code' => 'AB-002', 'merk' => 'Komatsu PC200-8M0 + JTHB 210', 'spes' => '138 HP', 'tarif' => 360000, 'satuan' => 'per jam', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'alat-berat', 'name' => 'Wheel Loader Komatsu WA200-5', 'code' => 'AB-003', 'merk' => 'Komatsu WA200-5', 'spes' => '123 HP', 'tarif' => 250000, 'satuan' => 'per jam', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'alat-berat', 'name' => 'Tandem Roller Bomag BW131AD-3', 'code' => 'AB-004', 'merk' => 'Bomag BW131AD-3', 'spes' => '28,4 HP / 4 Ton', 'tarif' => 170000, 'satuan' => 'per jam', 'kondisi' => 'Tidak Operasional', 'status' => 'Tidak Operasional', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'alat-berat', 'name' => 'Bulldozer Komatsu D31Ex', 'code' => 'AB-005', 'merk' => 'Komatsu D31Ex', 'spes' => '80 HP', 'tarif' => 300000, 'satuan' => 'per jam', 'kondisi' => 'Tidak Operasional', 'status' => 'Tidak Operasional', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'alat-berat', 'name' => 'Backhoe Loader Caterpillar CAT 416F2', 'code' => 'AB-006', 'merk' => 'Caterpillar CAT 416F2', 'spes' => '92 HP', 'tarif' => 250000, 'satuan' => 'per jam', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
            // Kendaraan — Alat Angkut PUPR
            ['cat' => 'kendaraan', 'name' => 'Dump Truck Toyota Dyna 110', 'code' => 'KN-001', 'merk' => 'Toyota Dyna 110', 'tarif' => 350000, 'satuan' => 'per hari', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'kendaraan', 'name' => 'Dump Truck Toyota Dyna 130', 'code' => 'KN-002', 'merk' => 'Toyota Dyna 130', 'tarif' => 400000, 'satuan' => 'per hari', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'kendaraan', 'name' => 'Flat Deck Truck Toyota Dyna 110', 'code' => 'KN-003', 'merk' => 'Toyota Dyna 110', 'tarif' => 350000, 'satuan' => 'per hari', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'kendaraan', 'name' => 'Self Loader Tronton (0-20 Km)', 'code' => 'KN-004', 'merk' => 'Tronton', 'spes' => '0-20 Km', 'tarif' => 450000, 'satuan' => 'per km', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'kendaraan', 'name' => 'Self Loader Tronton (80-100 Km)', 'code' => 'KN-005', 'merk' => 'Tronton', 'spes' => '80-100 Km', 'tarif' => 2700000, 'satuan' => 'per km', 'kondisi' => 'Baik', 'status' => 'Operasional', 'lokasi' => 'UPT PUPR Baubau'],
            ['cat' => 'kendaraan', 'name' => 'Mobil Sedot Tinja', 'code' => 'KN-006', 'merk' => 'Sedot Tinja DT9062', 'tarif' => 350000, 'satuan' => 'per hari', 'kondisi' => 'Terbatas', 'status' => 'Terbatas', 'lokasi' => 'UPT PUPR Baubau'],
        ];

        foreach ($items as $i) {
            $exists = DB::table('asset_items')->where('code', $i['code'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('asset_items')->insert([
                'category_id' => $cat[$i['cat']],
                'opd_id' => null, // konfirmasi OPD via ASET-R8
                'name' => $i['name'],
                'code' => $i['code'],
                'merk_type' => $i['merk'] ?? null,
                'spesifikasi' => $i['spes'] ?? null,
                'kondisi' => $i['kondisi'],
                'status_operasional' => $i['status'],
                'lokasi' => $i['lokasi'],
                'pic' => null,
                'tarif_default' => $i['tarif'],
                'satuan_tarif' => $i['satuan'],
                'is_demo' => true,
                'is_active' => true,
                'metadata' => json_encode(['source' => 'placeholder PERDA 1/2024 + matriks stakeholder']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('AssetModuleDemoSeeder: ' . count($items) . ' asset items (is_demo=1) seeded.');
    }
}
