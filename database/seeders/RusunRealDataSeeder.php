<?php

namespace Database\Seeders;

use App\Models\AssetRental;
use App\Models\Taxpayer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RusunRealDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/rusun_real_data.json');
        if (! File::exists($jsonPath)) {
            $this->command->error("File {$jsonPath} tidak ditemukan.");

            return;
        }

        $allData = json_decode(File::get($jsonPath), true);
        if (! $allData) {
            $this->command->error("Format JSON pada {$jsonPath} tidak valid.");

            return;
        }

        // 1. Pastikan Kategori Bangunan
        $categoryId = DB::table('asset_categories')->where('slug', 'bangunan')->value('id');
        if (! $categoryId) {
            $categoryId = DB::table('asset_categories')->insertGetId([
                'name' => 'Bangunan',
                'slug' => 'bangunan',
                'description' => 'Aset Bangunan dan Gedung Daerah',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Pastikan OPD Disperkim (ID 11)
        $perkimOpd = DB::table('opds')->where('id', 11)->orWhere('name', 'LIKE', '%Perumahan%')->first();
        if (! $perkimOpd) {
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

        // 3. Pastikan Master Item Rusunawa Lengkap (Perda No. 1/2024)
        $masterItems = [
            // Kotamara (Lantai 1-4)
            'kotamara' => [
                1 => ['code' => 'RUSUN-KOTAMARA-I', 'name' => 'Rusun Kotamara Lantai I', 'tarif' => 250000, 'lokasi' => 'Kotamara, Baubau'],
                2 => ['code' => 'RUSUN-KOTAMARA-II', 'name' => 'Rusun Kotamara Lantai II', 'tarif' => 225000, 'lokasi' => 'Kotamara, Baubau'],
                3 => ['code' => 'RUSUN-KOTAMARA-III', 'name' => 'Rusun Kotamara Lantai III', 'tarif' => 200000, 'lokasi' => 'Kotamara, Baubau'],
                4 => ['code' => 'RUSUN-KOTAMARA-IV', 'name' => 'Rusun Kotamara Lantai IV', 'tarif' => 175000, 'lokasi' => 'Kotamara, Baubau'],
            ],
            // Wameo (Lantai 1-4)
            'wameo' => [
                1 => ['code' => 'RUSUN-WAMEO-I', 'name' => 'Rusun Wameo Lantai I', 'tarif' => 200000, 'lokasi' => 'Wameo, Baubau'],
                2 => ['code' => 'RUSUN-WAMEO-II', 'name' => 'Rusun Wameo Lantai II', 'tarif' => 175000, 'lokasi' => 'Wameo, Baubau'],
                3 => ['code' => 'RUSUN-WAMEO-III', 'name' => 'Rusun Wameo Lantai III', 'tarif' => 150000, 'lokasi' => 'Wameo, Baubau'],
                4 => ['code' => 'RUSUN-WAMEO-IV', 'name' => 'Rusun Wameo Lantai IV', 'tarif' => 125000, 'lokasi' => 'Wameo, Baubau'],
            ],
            // ASN Palagimata (Lantai 1-4)
            'asn' => [
                1 => ['code' => 'RUSUN-ASN-I', 'name' => 'Rusun ASN Lantai I', 'tarif' => 350000, 'lokasi' => 'Palagimata, Baubau'],
                2 => ['code' => 'RUSUN-ASN-II', 'name' => 'Rusun ASN Lantai II', 'tarif' => 325000, 'lokasi' => 'Palagimata, Baubau'],
                3 => ['code' => 'RUSUN-ASN-III', 'name' => 'Rusun ASN Lantai III', 'tarif' => 300000, 'lokasi' => 'Palagimata, Baubau'],
                4 => ['code' => 'RUSUN-ASN-IV', 'name' => 'Rusun ASN Lantai IV', 'tarif' => 275000, 'lokasi' => 'Palagimata, Baubau'],
            ],
        ];

        $assetItemMap = []; // [complex][floor] => id

        foreach ($masterItems as $complex => $floors) {
            foreach ($floors as $floor => $cfg) {
                $item = DB::table('asset_items')->where('code', $cfg['code'])->first();
                if ($item) {
                    DB::table('asset_items')->where('id', $item->id)->update([
                        'category_id' => $categoryId,
                        'opd_id' => $opdId,
                        'name' => $cfg['name'],
                        'tarif_default' => $cfg['tarif'],
                        'satuan_tarif' => 'per bulan',
                        'kondisi' => 'Baik',
                        'status_operasional' => 'Operasional',
                        'lokasi' => $cfg['lokasi'],
                        'is_active' => true,
                        'metadata' => json_encode(['complex_code' => $complex, 'floor' => $floor]),
                        'updated_at' => now(),
                    ]);
                    $assetItemMap[$complex][$floor] = $item->id;
                } else {
                    $newId = DB::table('asset_items')->insertGetId([
                        'category_id' => $categoryId,
                        'opd_id' => $opdId,
                        'name' => $cfg['name'],
                        'code' => $cfg['code'],
                        'kondisi' => 'Baik',
                        'status_operasional' => 'Operasional',
                        'lokasi' => $cfg['lokasi'],
                        'pic' => 'Dinas Perkim Kota Baubau',
                        'tarif_default' => $cfg['tarif'],
                        'satuan_tarif' => 'per bulan',
                        'buffer_default' => 0,
                        'is_demo' => false,
                        'is_active' => true,
                        'metadata' => json_encode(['complex_code' => $complex, 'floor' => $floor]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $assetItemMap[$complex][$floor] = $newId;
                }
            }
        }

        // Hapus sewa & kamar dummy/lama khusus complex yang di-update
        $targetComplexCodes = array_keys($allData);
        DB::table('asset_rentals')->where('jenis_pekerjaan', 'Sewa Hunian Rusunawa')->delete();
        DB::table('rusun_rooms')->whereIn('complex_code', $targetComplexCodes)->delete();

        $totalInserted = 0;
        $totalRentals = 0;

        foreach ($allData as $complexKey => $rooms) {
            $complexNameLabel = match ($complexKey) {
                'wameo' => 'Rusunawa Wameo',
                'asn' => 'Rusunawa ASN Palagimata',
                'kotamara' => 'Rusunawa Kotamara',
                default => 'Rusunawa '.ucfirst($complexKey),
            };

            foreach ($rooms as $r) {
                $floor = (int) $r['floor'];
                $assetItemId = $assetItemMap[$complexKey][$floor] ?? null;
                if (! $assetItemId) {
                    continue;
                }

                $roomNumber = (string) $r['room_number'];
                $status = $r['status'] ?? 'available';
                $notes = $r['notes'] ?? null;
                $penghuniName = ! empty($r['penghuni']) ? trim((string) $r['penghuni']) : null;
                $phone = ! empty($r['phone']) ? trim((string) $r['phone']) : null;
                $tglMasuk = ! empty($r['tgl_masuk']) ? $r['tgl_masuk'] : '2026-01-01';
                $uangJaminan = $r['uang_jaminan'] ?? null;

                // 1. Buat Room
                $roomId = DB::table('rusun_rooms')->insertGetId([
                    'asset_item_id' => $assetItemId,
                    'complex_code' => $complexKey,
                    'floor' => $floor,
                    'room_number' => $roomNumber,
                    'status' => $status,
                    'notes' => $status === 'occupied' 
                        ? ('Sudah Terisi' . ($penghuniName ? ' - Dihuni: ' . $penghuniName : '') . ($notes ? " ({$notes})" : ''))
                        : ($status === 'maintenance' ? ($notes ?: 'Kamar Rusak') : 'Siap Huni'),
                    'current_rental_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $totalInserted++;

                // 2. Jika occupied, buat Taxpayer & AssetRental
                if ($status === 'occupied' && $penghuniName) {
                    // Cari atau buat Taxpayer
                    $taxpayer = Taxpayer::where('name', $penghuniName)
                        ->where('opd_id', $opdId)
                        ->first();

                    if (! $taxpayer) {
                        $randomDigits = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
                        $nik = '747201'.date('dmy').$randomDigits;

                        $taxpayer = Taxpayer::create([
                            'opd_id' => $opdId,
                            'nik' => $nik,
                            'name' => $penghuniName,
                            'address' => "{$complexNameLabel} Kamar {$roomNumber}, Kota Baubau",
                            'phone' => $phone,
                            'is_active' => true,
                            'metadata' => [
                                'tipe' => 'Penghuni Rusunawa',
                                'kompleks' => $complexKey,
                                'kamar' => $roomNumber,
                                'floor' => $floor,
                            ],
                        ]);
                    }

                    $monthlyRate = $masterItems[$complexKey][$floor]['tarif'] ?? 200000;
                    $rentalCode = 'RENT-'.strtoupper(substr($complexKey, 0, 3)).'-'.str_replace([' ', '-', '/'], '', $roomNumber);
                    $kontrakNo = 'SKR/DISPERKIM/2026/'.str_pad((string) $roomId, 4, '0', STR_PAD_LEFT);

                    $rentalId = DB::table('asset_rentals')->insertGetId([
                        'rental_code' => $rentalCode,
                        'nomor_kontrak' => $kontrakNo,
                        'taxpayer_id' => $taxpayer->id,
                        'asset_item_id' => $assetItemId,
                        'room_id' => $roomId,
                        'tanggal_mulai' => $tglMasuk,
                        'tanggal_selesai' => '2026-12-31',
                        'lama_sewa' => 12,
                        'satuan_sewa' => 'bulan',
                        'periode_bulan' => 12,
                        'buffer_hari' => 0,
                        'tarif_per_satuan' => $monthlyRate,
                        'total_biaya' => $monthlyRate * 12,
                        'dp' => $uangJaminan ?? ($monthlyRate * 3),
                        'is_guarantee_paid' => $uangJaminan ? true : false,
                        'guarantee_paid_at' => $uangJaminan ? now() : null,
                        'sisa_pembayaran' => 0,
                        'metode_pembayaran' => 'transfer',
                        'status' => 'Disetujui',
                        'lokasi_penggunaan' => "{$complexNameLabel} Kamar {$roomNumber}",
                        'jenis_pekerjaan' => 'Sewa Hunian Rusunawa',
                        'penyelia' => 'Pengelola Rusunawa Disperkim',
                        'catatan_approval' => 'Penghuni aktif terdaftar dalam SK / Data Disperkim Kota Baubau 2026',
                        'tanggal_approval' => now(),
                        'metadata' => json_encode([
                            'penghuni' => $penghuniName,
                            'complex' => $complexKey,
                            'room' => $roomNumber,
                            'uang_jaminan' => $uangJaminan,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Hubungkan current_rental_id pada kamar
                    DB::table('rusun_rooms')->where('id', $roomId)->update([
                        'current_rental_id' => $rentalId,
                    ]);

                    $totalRentals++;
                }
            }
        }

        $this->command->info('Seeding Rusunawa selesai!');
        $this->command->info("- Total Unit Kamar dimasukkan: {$totalInserted}");
        $this->command->info("- Total Penghuni Aktif & Sewa Disetujui: {$totalRentals}");
    }
}
