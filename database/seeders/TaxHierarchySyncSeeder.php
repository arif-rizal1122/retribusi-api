<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\PbbNjopClassification;
use App\Models\Zone;
use Illuminate\Database\Seeder;

/**
 * Sync ALL classifications with:
 *   - Cloudinary icons (inherited from parent type)
 *   - calculation_formula (Perda 1/2024 & Perwali 58/2024)
 *   - form_schema (input fields for calculations)
 *   - Proper codes
 * 
 * This seeder is IDEMPOTENT: safe to run multiple times.
 */
class TaxHierarchySyncSeeder extends Seeder
{
    // --- Cloudinary Icon URLs ---
    const ICON_PAJAK   = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855477/retribusi/icons/cixchfed9fiadty2c4a1.jpg';
    const ICON_REKLAME = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855480/retribusi/icons/airqm7ydazqqpsqezlrv.jpg';
    const ICON_MBLB    = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855474/retribusi/icons/pl1ag8vgja8jwzabaavc.jpg';
    const ICON_WALET   = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855486/retribusi/icons/agqc0orhv7i9wg4a7x1t.jpg';
    const ICON_BPHTB   = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855470/retribusi/icons/tjhkxpabcvlhjegvnzyf.jpg';
    const ICON_RETRIB  = 'https://res.cloudinary.com/ddhgtgsed/image/upload/v1769855483/retribusi/icons/mqbtlhf4modvik6ikhvi.jpg';

    // Common data-collection requirements
    private array $commonReqs = [
        ['key' => 'foto_lokasi_open_kamera', 'label' => 'Dokumentasi Open Kamera', 'required' => true],
        ['key' => 'formulir_data_dukung', 'label' => 'Upload Formulir Data Dukung', 'required' => true],
    ];

    public function run(): void
    {
        $bapenda = Opd::where('code', 'BAPENDA')->first();
        if (!$bapenda) {
            $this->command->error('BAPENDA OPD not found.');
            return;
        }

        // ─── Step 1: Ensure only 2 Master Types exist (Wilayah I & II) ──────────
        $this->syncTypeIcons($bapenda);

        $w1 = RetributionType::where('opd_id', $bapenda->id)->where('name', 'Wilayah I')->first();
        $w2 = RetributionType::where('opd_id', $bapenda->id)->where('name', 'Wilayah II')->first();

        if (!$w1 || !$w2) {
            $this->command->error("Wilayah I or II not found.");
            return;
        }

        // ─── Step 2: Sync Classifications according to Perwali 8/2025 ──────────

        // --- WILAYAH I (Official Assessment & Assets) ---
        $this->command->info('🔄 Syncing Wilayah I (Bidang Pendapatan I)...');

        // Generate NJOP Class Options for PBB-P2 dynamically with labels
        $bumiClasses = PbbNjopClassification::where('type', 'bumi')
            ->orderBy('class_code', 'asc')
            ->get()
            ->map(fn($c) => [
                'value' => $c->class_code,
                'label' => "Kelas {$c->class_code} | Rp " . number_format($c->min_value, 0, ',', '.') . " - " . number_format($c->max_value, 0, ',', '.')
            ])->toArray();

        $bangunanClasses = PbbNjopClassification::where('type', 'bangunan')
            ->orderBy('class_code', 'asc')
            ->get()
            ->map(fn($c) => [
                'value' => $c->class_code,
                'label' => "Kelas {$c->class_code} | Rp " . number_format($c->min_value, 0, ',', '.') . " - " . number_format($c->max_value, 0, ',', '.')
            ])->toArray();

        $this->syncClassification($bapenda, $w1, 'PBB-P2', [
            'code' => 'PBB-UMUM', 
            'icon' => self::ICON_PAJAK, 
            'formula' => '(njop - 10000000) * (njkp_percent / 100) * (tariff / 100)',
            'schema' => [
                ['key' => 'luas_tanah', 'label' => 'Luas Tanah (m2)', 'type' => 'number', 'required' => true],
                ['key' => 'kelas_bumi', 'label' => 'Kelas NJOP Bumi', 'type' => 'select', 'options' => $bumiClasses, 'required' => true],
                ['key' => 'luas_bangunan', 'label' => 'Luas Bangunan (m2)', 'type' => 'number', 'required' => true],
                ['key' => 'kelas_bangunan', 'label' => 'Kelas NJOP Bangunan', 'type' => 'select', 'options' => $bangunanClasses, 'required' => true],
                ['key' => 'nomor_sertifikat', 'label' => 'Nomor Sertifikat (SHM/HGB)', 'type' => 'text', 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [
                ['name' => 'Lahan Produksi Pangan 0.25%', 'amount' => 0.25, 'unit' => '%', 'formula' => '(njop - 10000000) * (njkp_percent / 100) * 0.0025'],
                ['name' => 'Umum/Lainnya 0.3%', 'amount' => 0.3, 'unit' => '%', 'formula' => '(njop - 10000000) * (njkp_percent / 100) * 0.003'],
            ],
        ]);

        $this->syncClassification($bapenda, $w1, 'BPHTB', [
            'code' => 'BPHTB', 'icon' => self::ICON_BPHTB, 'formula' => '(npop - npoptkp) * 0.05',
            'schema' => [
                ['key' => 'npop', 'label' => 'NPOP (Nilai Perolehan Objek Pajak)', 'type' => 'number', 'required' => true],
                ['key' => 'npoptkp', 'label' => 'NPOPTKP (Nilai Tidak Kena Pajak)', 'type' => 'number', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif BPHTB 5%', 'amount' => 5, 'unit' => '%', 'formula' => '(npop - npoptkp) * 0.05']],
        ]);

        $this->syncClassification($bapenda, $w1, 'Pajak Reklame', [
            'code' => 'REKLAME', 'icon' => self::ICON_REKLAME, 'formula' => 'nsr * 0.25',
            'schema' => [
                ['key' => 'nsr', 'label' => 'NSR (Nilai Sewa Reklame)', 'type' => 'number', 'required' => true],
                ['key' => 'ukuran', 'label' => 'Ukuran (m2)', 'type' => 'number', 'required' => true],
                ['key' => 'lokasi_reklame', 'label' => 'Lokasi Pemasangan', 'type' => 'text', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif Reklame 25%', 'amount' => 25, 'unit' => '%', 'formula' => 'nsr * 0.25']],
        ]);

        $this->syncClassification($bapenda, $w1, 'Pajak Sarang Burung Walet', [
            'code' => 'WALET', 'icon' => self::ICON_WALET, 'formula' => 'nilai_jual * 0.10',
            'schema' => [
                ['key' => 'nilai_jual', 'label' => 'Nilai Jual Sarang (Rp)', 'type' => 'number', 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif Walet 10%', 'amount' => 10, 'unit' => '%', 'formula' => 'nilai_jual * 0.10']],
        ]);

        $this->syncClassification($bapenda, $w1, 'Pajak MBLB', [
            'code' => 'MBLB', 'icon' => self::ICON_MBLB, 'formula' => '(volume * harga_patokan) * 0.15',
            'schema' => [
                ['key' => 'volume', 'label' => 'Volume (m3 / Ton)', 'type' => 'number', 'required' => true],
                ['key' => 'harga_patokan', 'label' => 'Harga Patokan', 'type' => 'number', 'required' => true],
                ['key' => 'jenis_mineral', 'label' => 'Jenis Mineral/Batuan', 'type' => 'text', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif MBLB 15%', 'amount' => 15, 'unit' => '%', 'formula' => '(volume * harga_patokan) * 0.15']],
        ]);

        $this->syncClassification($bapenda, $w1, 'Opsen PKB', [
            'code' => 'OPS-PKB', 'icon' => self::ICON_PAJAK, 'formula' => 'pkb_pokok * 0.66',
            'schema' => [['key' => 'pkb_pokok', 'label' => 'PKB Pokok (dari Provinsi)', 'type' => 'number', 'required' => true]],
            'rates' => [['name' => 'Tarif Opsen PKB 66%', 'amount' => 66, 'unit' => '%', 'formula' => 'pkb_pokok * 0.66']],
        ]);

        $this->syncClassification($bapenda, $w1, 'Opsen BBNKB', [
            'code' => 'OPS-BBN', 'icon' => self::ICON_PAJAK, 'formula' => 'bbnkb_pokok * 0.66',
            'schema' => [['key' => 'bbnkb_pokok', 'label' => 'BBNKB Pokok (dari Provinsi)', 'type' => 'number', 'required' => true]],
            'rates' => [['name' => 'Tarif Opsen BBNKB 66%', 'amount' => 66, 'unit' => '%', 'formula' => 'bbnkb_pokok * 0.66']],
        ]);

        // --- WILAYAH II (Self Assessment & Services & Retribusi) ---
        $this->command->info('🔄 Syncing Wilayah II (Bidang Pendapatan II)...');

        $this->syncClassification($bapenda, $w2, 'PBJT - Makan dan Minum', [
            'code' => 'PBJT-MNM', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10',
            'schema' => [
                ['key' => 'omzet', 'label' => 'Omzet Penjualan (Rata-rata/Bulan)', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan_usaha', 'label' => 'Keterangan Usaha', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif Standar 10%', 'amount' => 10, 'unit' => '%', 'formula' => 'omzet * 0.10']],
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Tenaga Listrik', [
            'code' => 'PBJT-LIS', 'icon' => self::ICON_PAJAK, 'formula' => 'tagihan_listrik * tariff',
            'schema' => [
                ['key' => 'tagihan_listrik', 'label' => 'Tagihan Listrik / Bulan', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan_usaha', 'label' => 'Keterangan Usaha', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [
                ['name' => 'Umum 10%', 'amount' => 10, 'unit' => '%', 'formula' => 'tagihan_listrik * 0.10'],
                ['name' => 'Industri 3%', 'amount' => 3, 'unit' => '%', 'formula' => 'tagihan_listrik * 0.03'],
                ['name' => 'Swadaya 1.5%', 'amount' => 1.5, 'unit' => '%', 'formula' => 'tagihan_listrik * 0.015'],
            ],
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Jasa Perhotelan', [
            'code' => 'PBJT-HTL', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10',
            'schema' => [
                ['key' => 'omzet', 'label' => 'Omzet / Pendapatan Kamar (Rata-rata/Bulan)', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan_usaha', 'label' => 'Keterangan Usaha', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif Standar 10%', 'amount' => 10, 'unit' => '%', 'formula' => 'omzet * 0.10']],
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Jasa Parkir', [
            'code' => 'PBJT-PRK', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * 0.10',
            'schema' => [
                ['key' => 'omzet', 'label' => 'Pendapatan Parkir (Rata-rata/Bulan)', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan_usaha', 'label' => 'Keterangan Usaha', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif Standar 10%', 'amount' => 10, 'unit' => '%', 'formula' => 'omzet * 0.10']],
        ]);

        $this->syncClassification($bapenda, $w2, 'PBJT - Jasa Kesenian dan Hiburan', [
            'code' => 'PBJT-HBR', 'icon' => self::ICON_PAJAK, 'formula' => 'omzet * tariff',
            'schema' => [
                ['key' => 'omzet', 'label' => 'Omzet Penjualan (Rata-rata/Bulan)', 'type' => 'number', 'required' => true],
                ['key' => 'keterangan_usaha', 'label' => 'Keterangan Usaha', 'type' => 'select', 'options' => ['Aktif', 'Tidak Aktif'], 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
            'rates' => [
                ['name' => 'Umum 10%', 'amount' => 10, 'unit' => '%', 'formula' => 'omzet * 0.10'],
                ['name' => 'Hiburan Malam (Khusus) 40%', 'amount' => 40, 'unit' => '%', 'formula' => 'omzet * 0.40'],
            ],
        ]);

        $this->syncClassification($bapenda, $w2, 'Pajak Air Tanah', [
            'code' => 'PAT', 'icon' => self::ICON_PAJAK, 'formula' => '(volume * hda) * 0.20',
            'schema' => [
                ['key' => 'volume', 'label' => 'Volume Pengambilan (m3)', 'type' => 'number', 'required' => true],
                ['key' => 'hda', 'label' => 'HDA (Harga Dasar Air)', 'type' => 'number', 'required' => true],
            ],
            'rates' => [['name' => 'Tarif PAT 20%', 'amount' => 20, 'unit' => '%', 'formula' => '(volume * hda) * 0.20']],
        ]);

        $this->syncClassification($bapenda, $w2, 'Penyediaan Tempat Kegiatan Usaha', [
            'code' => 'PTKU', 'icon' => self::ICON_RETRIB, 'formula' => 'amount',
            'schema' => [
                ['key' => 'jenis_usaha', 'label' => 'Jenis Usaha', 'type' => 'text', 'required' => true],
                ['key' => 'lokasi_google_maps', 'label' => 'Link Lokasi Google Maps', 'type' => 'text', 'required' => true],
            ],
        ]);

        $this->syncClassification($bapenda, $w2, 'Retribusi Jasa Umum', [
            'code' => 'RJU', 'icon' => self::ICON_RETRIB, 'formula' => 'amount',
            'schema' => [['key' => 'jenis_layanan', 'label' => 'Jenis Layanan', 'type' => 'text', 'required' => true]],
            'rates' => [
                ['name' => 'Konsultasi Dokter Spesialis', 'amount' => 80000, 'unit' => 'Kunjungan', 'formula' => '80000'],
                ['name' => 'Rawat Inap Kelas 3', 'amount' => 50000, 'unit' => 'Hari', 'formula' => '50000'],
                ['name' => 'Persalinan Normal', 'amount' => 1338100, 'unit' => 'Tindakan', 'formula' => '1338100'],
                ['name' => 'Sampah Rumah Tangga (Min)', 'amount' => 8500, 'unit' => 'Bulan', 'formula' => '8500'],
                ['name' => 'Sampah Rumah Tangga (Maks)', 'amount' => 25000, 'unit' => 'Bulan', 'formula' => '25000'],
                ['name' => 'Parkir Tepi Jalan - Roda 2', 'amount' => 2000, 'unit' => 'Kali', 'formula' => '2000'],
                ['name' => 'Parkir Tepi Jalan - Roda 4', 'amount' => 3000, 'unit' => 'Kali', 'formula' => '3000'],
            ],
        ]);

        $this->syncClassification($bapenda, $w2, 'Retribusi Perizinan Tertentu', [
            'code' => 'RPT', 'icon' => self::ICON_RETRIB, 'formula' => 'amount',
            'schema' => [['key' => 'jenis_izin', 'label' => 'Jenis Izin', 'type' => 'text', 'required' => true]],
        ]);

        $this->syncClassification($bapenda, $w2, 'Persetujuan Bangunan Gedung (PBG)', [
            'code' => 'PBG', 'icon' => self::ICON_RETRIB, 'formula' => 'luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg',
            'schema' => [
                ['key' => 'luas_lantai', 'label' => 'Luas Lantai (m2)', 'type' => 'number', 'required' => true],
                ['key' => 'indeks_lokalitas', 'label' => 'Indeks Lokalitas', 'type' => 'number', 'required' => true],
                ['key' => 'shst', 'label' => 'SHST (Sederhana: 5.56jt, Non: 7.06jt)', 'type' => 'number', 'required' => true],
                ['key' => 'indeks_terintegrasi', 'label' => 'Indeks Terintegrasi', 'type' => 'number', 'required' => true],
                ['key' => 'indeks_bg', 'label' => 'Indeks Bangunan Gedung', 'type' => 'number', 'required' => true],
            ],
            'rates' => [['name' => 'PBG Bangunan Baru', 'amount' => 1, 'unit' => 'Indeks', 'formula' => 'luas_lantai * (indeks_lokalitas * shst) * indeks_terintegrasi * indeks_bg']],
        ]);

        $this->command->info('✅ All classifications synced under Perwali 8/2025 functional split!');
    }

    private function syncTypeIcons($opd)
    {
        $names = ['Wilayah I', 'Wilayah II'];
        $keepIds = [];

        foreach ($names as $name) {
            $type = RetributionType::updateOrCreate(
                ['name' => $name, 'opd_id' => $opd->id],
                [
                    'category' => 'Pajak', // Default master category
                    'icon' => self::ICON_PAJAK,
                    'is_active' => true,
                ]
            );
            $keepIds[] = $type->id;
        }
        
        // --- STRICT HIERARCHY ENFORCEMENT ---
        // 1. Deactivate all other types for BAPENDA
        RetributionType::where('opd_id', $opd->id)
            ->whereNotIn('id', $keepIds)
            ->update(['is_active' => false]);

        // 2. Remove classifications not belonging to Wilayah I or II for BAPENDA
        // (Safe because we verified no bills/objects are linked to them)
        RetributionClassification::where('opd_id', $opd->id)
            ->whereNotIn('retribution_type_id', $keepIds)
            ->delete();
    }

    private function syncClassification($opd, $type, string $name, array $config)
    {
        $cls = RetributionClassification::updateOrCreate(
            ['retribution_type_id' => $type->id, 'name' => $name],
            [
                'opd_id' => $opd->id,
                'code' => $config['code'],
                'icon' => $config['icon'],
                'calculation_formula' => $config['formula'],
                'form_schema' => $config['schema'],
                'requirements' => $this->commonReqs,
            ]
        );

        if (!empty($config['rates'])) {
            foreach ($config['rates'] as $rate) {
                RetributionRate::updateOrCreate(
                    ['retribution_classification_id' => $cls->id, 'name' => $rate['name']],
                    [
                        'opd_id' => $opd->id,
                        'retribution_type_id' => $type->id,
                        'amount' => $rate['amount'],
                        'unit' => $rate['unit'],
                        'is_active' => true,
                        'calculation_formula' => $rate['formula'] ?? null,
                    ]
                );
            }
        }
    }
}
