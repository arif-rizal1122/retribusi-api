<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        // 1. Seed / update 3 spesifik armada Mobil Sedot Tinja / Sedot Kakus UPTD PUPR Baubau
        $armadas = [
            [
                'code' => 'SK-001',
                'name' => 'Mobil Sedot Tinja Armada Utama (DT 8038 G)',
                'merk_type' => 'Isuzu Elf / Tangki Vakum 3000L',
                'spesifikasi' => 'Kapasitas 3.000 Liter / Selang 30 Meter / Pompa Vakum PTO',
                'kondisi' => 'Baik',
                'status_operasional' => 'Operasional',
                'lokasi' => 'UPTD Workshop & Alat Berat Dinas PUPR Baubau',
                'pic' => 'Ilan (Sopir) & Yudi (Operator)',
                'tarif_default' => 150000.00,
                'satuan_tarif' => 'per rit',
                'wajib_tronton' => false,
                'metadata' => [
                    'plat_nomor' => 'DT 8038 G',
                    'nomenklatur_perda' => 'Penyediaan dan/atau Penyedotan Kakus (Pasal 74 ayat 1 b jo. Pasal 77 ayat 1 d Perda 1/2024)',
                    'sopir' => 'Ilan',
                    'operator' => 'Yudi',
                    'kapasitas_liter' => 3000,
                    'status_layanan' => 'Priority 1 - Siap Beroperasi',
                ],
            ],
            [
                'code' => 'SK-002',
                'name' => 'Mobil Sedot Tinja Armada Cadangan (DT 9104 G)',
                'merk_type' => 'Toyota Dyna / Tangki Vakum 3000L',
                'spesifikasi' => 'Kapasitas 3.000 Liter / Cadangan Beban Puncak',
                'kondisi' => 'Terbatas',
                'status_operasional' => 'Terbatas',
                'lokasi' => 'UPTD Workshop & Alat Berat Dinas PUPR Baubau',
                'pic' => 'Adam (Sopir) & Dina (Operator)',
                'tarif_default' => 150000.00,
                'satuan_tarif' => 'per rit',
                'wajib_tronton' => false,
                'metadata' => [
                    'plat_nomor' => 'DT 9104 G',
                    'nomenklatur_perda' => 'Penyediaan dan/atau Penyedotan Kakus (Perda Baubau 1/2024)',
                    'sopir' => 'Adam',
                    'operator' => 'Dina',
                    'kapasitas_liter' => 3000,
                    'status_layanan' => 'Priority 2 - Armada Cadangan',
                ],
            ],
            [
                'code' => 'SK-003',
                'name' => 'Mobil Sedot Tinja Pemeliharaan (DT 9062)',
                'merk_type' => 'Mitsubishi Canter / Tangki Vakum',
                'spesifikasi' => 'Kapasitas 3.000 Liter / Pompa hisap perbaikan',
                'kondisi' => 'Tidak Operasional',
                'status_operasional' => 'Tidak Operasional',
                'lokasi' => 'Bengkel Workshop UPTD PUPR Baubau',
                'pic' => 'Teknisi Workshop PUPR',
                'tarif_default' => 150000.00,
                'satuan_tarif' => 'per rit',
                'wajib_tronton' => false,
                'metadata' => [
                    'plat_nomor' => 'DT 9062',
                    'nomenklatur_perda' => 'Penyediaan dan/atau Penyedotan Kakus',
                    'status_layanan' => 'Maintenance / Dalam Perbaikan',
                ],
            ],
        ];

        foreach ($armadas as $a) {
            $existing = DB::table('asset_items')->where('code', $a['code'])->first();
            $data = [
                'category_id' => $catKendaraan,
                'opd_id' => $opdId,
                'name' => $a['name'],
                'code' => $a['code'],
                'merk_type' => $a['merk_type'],
                'spesifikasi' => $a['spesifikasi'],
                'kondisi' => $a['kondisi'],
                'status_operasional' => $a['status_operasional'],
                'lokasi' => $a['lokasi'],
                'pic' => $a['pic'],
                'tarif_default' => $a['tarif_default'],
                'satuan_tarif' => $a['satuan_tarif'],
                'wajib_tronton' => $a['wajib_tronton'],
                'buffer_default' => 1,
                'is_demo' => false,
                'is_active' => true,
                'metadata' => json_encode($a['metadata']),
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('asset_items')->where('id', $existing->id)->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('asset_items')->insert($data);
            }
        }

        // 2. Sample Bookings / Jadwal Pelayanan Sedot Kakus di Baubau
        $item1 = DB::table('asset_items')->where('code', 'SK-001')->first();
        $taxpayer = DB::table('taxpayers')->first();

        if ($item1 && $taxpayer) {
            $taxpayerId = $taxpayer->id;
            $bookings = [
                [
                    'rental_code' => 'KKS-202609-001',
                    'nomor_kontrak' => '01/KKS/UPTD-PUPR/IX/2026',
                    'tanggal_mulai' => '2026-09-11 08:30:00',
                    'tanggal_selesai' => '2026-09-11 11:30:00',
                    'lama_sewa' => 1,
                    'satuan_sewa' => 'per rit',
                    'tarif_per_satuan' => 150000,
                    'total_biaya' => 150000,
                    'dp' => 150000,
                    'sisa_pembayaran' => 0,
                    'status' => 'Disetujui',
                    'lokasi_penggunaan' => 'Jl. Murhum No. 42, Kel. Wajo, Kec. Murhum, Kota Baubau',
                    'jenis_pekerjaan' => 'Penyedotan Septic Tank Rumah Tinggal Penuh',
                    'nama_proyek' => 'Pelayanan Sedot Kakus Warga (Bpk. H. Rahmat)',
                ],
                [
                    'rental_code' => 'KKS-202609-002',
                    'nomor_kontrak' => '02/KKS/UPTD-PUPR/IX/2026',
                    'tanggal_mulai' => '2026-09-12 13:00:00',
                    'tanggal_selesai' => '2026-09-12 16:00:00',
                    'lama_sewa' => 1,
                    'satuan_sewa' => 'per rit',
                    'tarif_per_satuan' => 150000,
                    'total_biaya' => 150000,
                    'dp' => 150000,
                    'sisa_pembayaran' => 0,
                    'status' => 'Diverifikasi',
                    'lokasi_penggunaan' => 'Kompleks Ruko Pasar Wameo Blok C, Kota Baubau',
                    'jenis_pekerjaan' => 'Penyedotan Kakus Fasilitas Umum Ruko Pasar Wameo',
                    'nama_proyek' => 'Penyedotan Kakus Ruko Wameo',
                ],
                [
                    'rental_code' => 'KKS-202609-003',
                    'nomor_kontrak' => '03/KKS/UPTD-PUPR/IX/2026',
                    'tanggal_mulai' => '2026-09-14 09:00:00',
                    'tanggal_selesai' => '2026-09-14 12:00:00',
                    'lama_sewa' => 1,
                    'satuan_sewa' => 'per rit',
                    'tarif_per_satuan' => 150000,
                    'total_biaya' => 150000,
                    'dp' => 0,
                    'sisa_pembayaran' => 150000,
                    'status' => 'Diajukan',
                    'lokasi_penggunaan' => 'Jl. Pahlawan Km 4, Kel. Bukit Wolio Indah, Kota Baubau',
                    'jenis_pekerjaan' => 'Penyedotan Septic Tank Rumah Warga (Ibu Nurhayati)',
                    'nama_proyek' => 'Pelayanan Sedot Kakus Warga',
                ],
            ];

            foreach ($bookings as $b) {
                $exists = DB::table('asset_rentals')->where('rental_code', $b['rental_code'])->first();
                if (!$exists) {
                    DB::table('asset_rentals')->insert(array_merge($b, [
                        'taxpayer_id' => $taxpayerId,
                        'asset_item_id' => $item1->id,
                        'include_tronton' => false,
                        'biaya_tronton' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    public function down(): void
    {
    }
};
