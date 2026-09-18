<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder master Modul Pariwisata — OPD 16 (Dinas Pariwisata dan Kebudayaan Kota Baubau).
 * Idempotent: aman dijalankan berulang (updateOrInsert).
 * Tarif mengacu Perda Kota Baubau No. 1 Tahun 2024.
 */
class PariwisataMasterSeeder extends Seeder
{
    private const OPD_ID = 16;

    public function run(): void
    {
        $now = now();

        // 1. RetributionType — sektor Retribusi Objek Wisata (tabel TIDAK punya kolom `code`)
        $typeId = DB::table('retribution_types')->where('opd_id', self::OPD_ID)
            ->where('name', 'Retribusi Objek Wisata')->value('id');

        if (! $typeId) {
            $typeId = DB::table('retribution_types')->insertGetId([
                'opd_id' => self::OPD_ID,
                'name' => 'Retribusi Objek Wisata',
                'category' => 'Retribusi',
                'tariff_percent' => 0,
                'base_amount' => null,
                'unit' => 'orang',
                'billing_cycle' => 'daily',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Destinasi wisata
        $destinations = [
            [
                'code' => 'PAR-KERATON',
                'name' => 'Kawasan Cagar Budaya Benteng Keraton Wolio',
                'slug' => 'benteng-keraton-wolio',
                'daily_capacity' => 1500,
                'open_time' => '07:00',
                'close_time' => '18:00',
                'requires_offline_mode' => 0,
                'description' => 'Wisata budaya dan sejarah. Kuota harian 1.500 pengunjung.',
            ],
            [
                'code' => 'PAR-BATUSORI',
                'name' => 'Pantai Batu Sori',
                'slug' => 'pantai-batu-sori',
                'daily_capacity' => 500,
                'open_time' => '07:00',
                'close_time' => '18:00',
                'requires_offline_mode' => 1,
                'description' => 'Wisata pesisir, tebing bahari dan selam. Kuota harian 500 pengunjung.',
            ],
        ];

        foreach ($destinations as $d) {
            DB::table('tourism_destinations')->updateOrInsert(
                ['code' => $d['code']],
                array_merge($d, [
                    'opd_id' => self::OPD_ID,
                    'session_quota' => (int) round($d['daily_capacity'] / 4),
                    'is_open' => 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // 3. Klasifikasi + tarif (matriks tarif Perda)
        $matrix = [
            'PAR-KERATON' => [
                ['Domestik Dewasa', 'DOMESTIC_ADULT', 10000],
                ['Pelajar / Mahasiswa', 'STUDENT', 5000],
                ['Wisatawan Mancanegara', 'FOREIGN', 50000],
            ],
            'PAR-BATUSORI' => [
                ['Domestik Dewasa', 'DOMESTIC_ADULT', 5000],
                ['Pelajar / Mahasiswa', 'STUDENT', 3000],
                ['Wisatawan Mancanegara', 'FOREIGN', 25000],
            ],
        ];

        foreach ($matrix as $destCode => $rates) {
            $destName = DB::table('tourism_destinations')->where('code', $destCode)->value('name');

            foreach ($rates as $row) {
                [$label, $visitorCategory, $amount] = $row;
                $classCode = $destCode . '-' . $visitorCategory;

                $classId = DB::table('retribution_classifications')
                    ->where('opd_id', self::OPD_ID)
                    ->where('code', $classCode)
                    ->value('id');

                if (! $classId) {
                    $classId = DB::table('retribution_classifications')->insertGetId([
                        'opd_id' => self::OPD_ID,
                        'retribution_type_id' => $typeId,
                        'name' => $destName . ' — ' . $label,
                        'code' => $classCode,
                        'is_self_assessment' => 0,
                        'calculation_formula' => 'pax * rate',
                        'description' => 'Tarif ' . $label . ' per orang.',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('retribution_rates')->updateOrInsert(
                    [
                        'opd_id' => self::OPD_ID,
                        'retribution_type_id' => $typeId,
                        'retribution_classification_id' => $classId,
                    ],
                    [
                        'name' => $label,
                        'amount' => $amount,
                        'unit' => 'per orang',
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // 4. Sewa kios cinderamata — tarif dasar Perda Rp 500.000/bulan
        $kiosCode = 'PAR-KIOS-CINDERAMATA';
        $kiosClassId = DB::table('retribution_classifications')
            ->where('opd_id', self::OPD_ID)->where('code', $kiosCode)->value('id');

        if (! $kiosClassId) {
            $kiosClassId = DB::table('retribution_classifications')->insertGetId([
                'opd_id' => self::OPD_ID,
                'retribution_type_id' => $typeId,
                'name' => 'Sewa Kios Cinderamata, Suvenir & Tenun Buton',
                'code' => $kiosCode,
                'is_self_assessment' => 0,
                'calculation_formula' => 'bulan * 500000',
                'description' => 'Tarif dasar terkunci Rp 500.000/bulan. Keringanan mengikuti Pasal 106 Perda 1/2024.',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('retribution_rates')->updateOrInsert(
            [
                'opd_id' => self::OPD_ID,
                'retribution_type_id' => $typeId,
                'retribution_classification_id' => $kiosClassId,
            ],
            [
                'name' => 'Sewa Kios per Bulan',
                'amount' => 500000,
                'unit' => 'per bulan',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 5. Modul aset — kategori & item sewa perlengkapan selam (OPD 16)
        $catId = DB::table('asset_categories')->where('slug', 'bahari-selam')->value('id');

        if (! $catId) {
            $catId = DB::table('asset_categories')->insertGetId([
                'name' => 'Perlengkapan Selam & Snorkeling',
                'slug' => 'bahari-selam',
                'description' => 'Sewa peralatan selam dan snorkeling Pantai Batu Sori.',
                'sort_order' => 10,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $items = [
            ['DIV-01', 'Paket Scuba Diving Gear', 250000, 'per hari'],
            ['SNK-01', 'Paket Snorkeling', 50000, 'per hari'],
        ];

        foreach ($items as $row) {
            [$code, $name, $tarif, $satuan] = $row;

            DB::table('asset_items')->updateOrInsert(
                ['code' => $code],
                [
                    'category_id' => $catId,
                    'opd_id' => self::OPD_ID,
                    'name' => $name,
                    'tarif_default' => $tarif,
                    'satuan_tarif' => $satuan,
                    'kondisi' => 'Baik',
                    'status_operasional' => 'Operasional',
                    'lokasi' => 'Pantai Batu Sori',
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $this->command->info('PariwisataMasterSeeder selesai. OPD 16 siap dipakai.');
    }
}
