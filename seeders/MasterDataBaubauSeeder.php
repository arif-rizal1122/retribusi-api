<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataBaubauSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get BAPENDA OPD
        $bapenda = Opd::updateOrCreate(
            ['code' => 'BAPENDA'],
            ['name' => 'Badan Pendapatan Daerah', 'is_active' => true]
        );

        // 2. Portfolio Consistency (ID 16 & 17)
        $portfolios = [
            16 => ['name' => 'Pendapatan Wilayah I (Aset & Properti)', 'category' => 'Pajak'],
            17 => ['name' => 'Pendapatan Wilayah II (Konsumsi & Self-Assessment)', 'category' => 'Pajak'],
        ];

        foreach ($portfolios as $id => $info) {
            RetributionType::updateOrCreate(
                ['id' => $id],
                [
                    'opd_id' => $bapenda->id,
                    'name' => $info['name'],
                    'category' => $info['category'],
                    'billing_cycle' => 'monthly',
                    'is_active' => true
                ]
            );
        }

        // 3. Define Master Classifications with Active Formulas & Form Schemas
        $classifications = [
            // WILAYAH I (Aset)
            [
                'type_id' => 16, 'name' => 'PBB-P2', 'code' => 'PBB-P2', 'formula' => '(njop - 10000000) * 0.003',
                'schema' => [
                    ['key' => 'njop', 'label' => 'NJOP Tanah & Bangunan', 'type' => 'number', 'required' => true],
                    ['key' => 'tahun_sppt', 'label' => 'Tahun Pajak', 'type' => 'number', 'required' => true],
                ]
            ],
            [
                'type_id' => 16, 'name' => 'BPHTB', 'code' => 'BPHTB', 'formula' => '(npop - 80000000) * 0.05',
                'schema' => [
                    ['key' => 'npop', 'label' => 'Nilai Perolehan Objek Pajak (NPOP)', 'type' => 'number', 'required' => true],
                    ['key' => 'jenis_perolehan', 'label' => 'Jenis Perolehan', 'type' => 'text', 'required' => true],
                ]
            ],
            [
                'type_id' => 16, 'name' => 'Pajak Reklame', 'code' => 'REKLAME', 'formula' => '((njopr + nspr) * luas * sisi) * 0.25',
                'schema' => [
                    ['key' => 'judul_reklame', 'label' => 'Naskah/Judul Reklame', 'type' => 'text', 'required' => true],
                    ['key' => 'luas', 'label' => 'Luas (m2)', 'type' => 'number', 'required' => true],
                    ['key' => 'sisi', 'label' => 'Jumlah Sisi (Muka)', 'type' => 'number', 'required' => true],
                    ['key' => 'njopr', 'label' => 'NJOP Reklame (Fisik)', 'type' => 'number', 'required' => true],
                    ['key' => 'nspr', 'label' => 'Nilai Strategis (NSPR)', 'type' => 'number', 'required' => true],
                ]
            ],
            [
                'type_id' => 16, 'name' => 'Pajak MBLB', 'code' => 'MBLB', 'formula' => '(volume * harga) * 0.15',
                'schema' => [
                    ['key' => 'volume', 'label' => 'Volume (m3 / Ton)', 'type' => 'number', 'required' => true],
                    ['key' => 'harga', 'label' => 'Harga Patokan Pasar', 'type' => 'number', 'required' => true],
                ]
            ],
            [
                'type_id' => 16, 'name' => 'Pajak Sarang Burung Walet', 'code' => 'WALET', 'formula' => '(volume * harga) * 0.10',
                'schema' => [
                    ['key' => 'volume', 'label' => 'Berat Panen (Kg)', 'type' => 'number', 'required' => true],
                    ['key' => 'harga', 'label' => 'Harga Pasar', 'type' => 'number', 'required' => true],
                ]
            ],
            [
                'type_id' => 16, 'name' => 'Opsen Pajak', 'code' => 'OPSEN', 'formula' => 'pokok_provinsi * 0.66',
                'schema' => [['key' => 'pokok_provinsi', 'label' => 'Pokok Pajak Provinsi', 'type' => 'number', 'required' => true]]
            ],

            // WILAYAH II (Konsumsi)
            [
                'type_id' => 17, 'name' => 'PBJT - Makan dan Minum', 'code' => 'PBJT-FOOD', 'formula' => 'omzet * 0.1',
                'schema' => [
                    ['key' => 'omzet', 'label' => 'Total Omzet (Pendapatan Kotor)', 'type' => 'number', 'required' => true],
                    ['key' => 'kapasitas_kursi', 'label' => 'Kapasitas Kursi', 'type' => 'number', 'required' => false],
                ]
            ],
            [
                'type_id' => 17, 'name' => 'PBJT - Jasa Perhotelan', 'code' => 'PBJT-HTL', 'formula' => 'omzet * 0.1',
                'schema' => [
                    ['key' => 'omzet', 'label' => 'Total Omzet (Pendapatan Kotor)', 'type' => 'number', 'required' => true],
                    ['key' => 'jumlah_kamar', 'label' => 'Jumlah Kamar Terisi', 'type' => 'number', 'required' => false],
                ]
            ],
            [
                'type_id' => 17, 'name' => 'PBJT - Jasa Kesenian dan Hiburan', 'code' => 'PBJT-HBR', 'formula' => 'omzet * 0.1',
                'schema' => [['key' => 'omzet', 'label' => 'Total Omzet (Nilai Tiket)', 'type' => 'number', 'required' => true]]
            ],
            [
                'type_id' => 17, 'name' => 'Hiburan Malam (Khusus)', 'code' => 'PBJT-HBR-SP', 'formula' => 'omzet * 0.4',
                'schema' => [['key' => 'omzet', 'label' => 'Total Omzet', 'type' => 'number', 'required' => true]]
            ],
            [
                'type_id' => 17, 'name' => 'PBJT - Jasa Parkir', 'code' => 'PBJT-PRK', 'formula' => 'omzet * 0.1',
                'schema' => [['key' => 'omzet', 'label' => 'Total Omzet Parkir', 'type' => 'number', 'required' => true]]
            ],
            [
                'type_id' => 17, 'name' => 'PBJT - Tenaga Listrik', 'code' => 'PBJT-PLN', 'formula' => 'tagihan * 0.1',
                'schema' => [['key' => 'tagihan', 'label' => 'Nilai Tagihan Listrik', 'type' => 'number', 'required' => true]]
            ],
            [
                'type_id' => 17, 'name' => 'Pajak Air Tanah', 'code' => 'AIR-TANAH', 'formula' => '(volume * hda) * 0.2',
                'schema' => [
                    ['key' => 'volume', 'label' => 'Volume Pemakaian (M3)', 'type' => 'number', 'required' => true],
                    ['key' => 'hda', 'label' => 'Harga Dasar Air (HDA)', 'type' => 'number', 'required' => true],
                ]
            ],
            [
                'type_id' => 17, 'name' => 'Retribusi Persampahan', 'code' => 'RET-SMP', 'formula' => 'tarif_flat',
                'schema' => [['key' => 'tarif_flat', 'label' => 'Nominal Retribusi Tetap', 'type' => 'number', 'required' => true]]
            ],
            [
                'type_id' => 17, 'name' => 'Retribusi Pelayanan Parkir', 'code' => 'RET-PRK', 'formula' => 'tarif_flat',
                'schema' => [['key' => 'tarif_flat', 'label' => 'Setoran Harian', 'type' => 'number', 'required' => true]]
            ],
            [
                'type_id' => 17, 'name' => 'Retribusi PKD (Kios/Pasar)', 'code' => 'RET-PKD', 'formula' => 'tarif_dasar * koefisien',
                'schema' => [
                    ['key' => 'tarif_dasar', 'label' => 'Tarif Dasar Lapak', 'type' => 'number', 'required' => true],
                    ['key' => 'koefisien', 'label' => 'Koefisien Zona', 'type' => 'number', 'required' => true],
                ]
            ],
        ];

        foreach ($classifications as $data) {
            RetributionClassification::updateOrCreate(
                ['code' => $data['code']],
                [
                    'retribution_type_id' => $data['type_id'],
                    'opd_id' => $bapenda->id,
                    'name' => $data['name'],
                    'calculation_formula' => $data['formula'],
                    'form_schema' => json_encode($data['schema']),
                    'is_self_assessment' => $data['type_id'] == 17 && !str_starts_with($data['code'], 'RET-'),
                ]
            );
        }

        // 4. Define Precise Zones (Road Classes & coefficients)
        $roadZones = [
            ['code' => 'RD-CL-A', 'name' => 'Kelas Jalan A (Sangat Strategis)', 'info' => 'Multiplier: 1.5 | Streets: RA Kartini, Yos Sudarso, Jend Sudirman, Pantai Kamali, Kotamara'],
            ['code' => 'RD-CL-B', 'name' => 'Kelas Jalan B (Strategis)', 'info' => 'Multiplier: 1.2 | Streets: Teuku Umar, RE Martadinata, Gatot Subroto'],
            ['code' => 'RD-CL-C', 'name' => 'Kelas Jalan C (Standar)', 'info' => 'Multiplier: 1.0 | Streets: Imam Bonjol, Seram'],
        ];

        foreach ($roadZones as $z) {
            Zone::updateOrCreate(
                ['code' => $z['code']],
                [
                    'name' => $z['name'],
                    'opd_id' => $bapenda->id,
                    'geometry_type' => 'polygon',
                    'description' => $z['info']
                ]
            );
        }

        // 5. PKD/Lapak Zones
        $lapakZones = [
            ['code' => 'PKD-PREM', 'name' => 'Zona Premium (1.3)', 'info' => 'Coefficient: 1.3'],
            ['code' => 'PKD-STRAT', 'name' => 'Zona Strategis (1.2)', 'info' => 'Coefficient: 1.2'],
            ['code' => 'PKD-ECON', 'name' => 'Zona Ekonomi (1.1)', 'info' => 'Coefficient: 1.1'],
            ['code' => 'PKD-COMM', 'name' => 'Zona Umum (1.0)', 'info' => 'Coefficient: 1.0'],
        ];

        foreach ($lapakZones as $z) {
            Zone::updateOrCreate(
                ['code' => $z['code']],
                [
                    'name' => $z['name'],
                    'opd_id' => $bapenda->id,
                    'geometry_type' => 'point',
                    'description' => $z['info']
                ]
            );
        }

        $this->command->info("✅ MITRA Master Data Seeded Successfully with formulas and zones.");
    }
}
