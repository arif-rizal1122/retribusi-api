<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;

class DlhMasterTariffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan klasifikasi RET-SMP terhubung ke DLH (opd_id = 4)
        $classification = RetributionClassification::where('code', 'RET-SMP')->first();
        if ($classification) {
            $classification->update([
                'opd_id' => 4,
                'name' => 'Retribusi Persampahan & Kebersihan',
                'calculation_formula' => 'tarif_flat',
                'form_schema' => json_encode([
                    ['key' => 'building_category', 'label' => 'Kategori Bangunan', 'type' => 'select', 'required' => true,
                     'options' => ['SOSIAL_IBADAH', 'RUMAH_SEDERHANA', 'RUMAH_MENENGAH', 'RUKO_NIAGA', 'RESTORAN', 'PASAR', 'INDUSTRI']],
                    ['key' => 'tipe_penghuni', 'label' => 'Tipe Penghuni', 'type' => 'select', 'required' => true, 'options' => ['PEMILIK', 'PENYEWA']],
                    ['key' => 'status_hunian', 'label' => 'Status Hunian', 'type' => 'select', 'required' => true, 'options' => ['BERPENGHUNI', 'KOSONG']],
                    ['key' => 'penanggung_jawab_nama', 'label' => 'Nama Penanggung Jawab', 'type' => 'text', 'required' => false],
                    ['key' => 'penanggung_jawab_kontak', 'label' => 'No. Telepon / WA', 'type' => 'text', 'required' => false],
                ])
            ]);
            $classId = $classification->id;
            $typeId = $classification->retribution_type_id;
        } else {
            $classId = 198;
            $typeId = 17;
        }

        // 2. Master Tarif Persampahan Perda No. 1 Tahun 2024
        $trashRates = [
            [
                'name' => 'Fasilitas Sosial, Panti Asuhan & Rumah Ibadah (Bebas Tarif)',
                'amount' => 0.00,
                'unit' => 'per bulan',
                'calculation_formula' => '0',
            ],
            [
                'name' => 'Rumah Tinggal Sederhana (Tipe < 36)',
                'amount' => 8500.00,
                'unit' => 'per bulan',
                'calculation_formula' => 'tarif_flat',
            ],
            [
                'name' => 'Rumah Tinggal Menengah / Mewah (Tipe >= 36)',
                'amount' => 15000.00,
                'unit' => 'per bulan',
                'calculation_formula' => 'tarif_flat',
            ],
            [
                'name' => 'Ruko / Kantor / Usaha Niaga',
                'amount' => 22000.00,
                'unit' => 'per bulan',
                'calculation_formula' => 'tarif_flat',
            ],
            [
                'name' => 'Rumah Makan / Restoran / Kafe',
                'amount' => 45000.00,
                'unit' => 'per bulan',
                'calculation_formula' => 'tarif_flat',
            ],
            [
                'name' => 'Karcis Sampah Harian Pasar (Pedagang Kecil)',
                'amount' => 3000.00,
                'unit' => 'per hari',
                'calculation_formula' => 'tarif_flat',
            ],
            [
                'name' => 'Karcis Sampah Harian Pasar (Pedagang Besar/Kios)',
                'amount' => 6000.00,
                'unit' => 'per hari',
                'calculation_formula' => 'tarif_flat',
            ],
        ];

        foreach ($trashRates as $rate) {
            RetributionRate::updateOrCreate(
                [
                    'opd_id' => 4,
                    'retribution_classification_id' => $classId,
                    'name' => $rate['name'],
                ],
                [
                    'retribution_type_id' => $typeId,
                    'amount' => $rate['amount'],
                    'unit' => $rate['unit'],
                    'calculation_formula' => $rate['calculation_formula'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Master 7 Parameter UPTD Laboratorium Lingkungan Hidup (Perda No. 1 Tahun 2024)
        $labParameters = [
            [
                'code' => 'LAB-PH',
                'name' => 'Derajat Keasaman (pH)',
                'method' => 'SNI 06-6989.11-2004 (Elektrometri)',
                'unit' => 'pH unit',
                'tariff' => 30000.00,
                'standard_reference' => '6.0 - 9.0'
            ],
            [
                'code' => 'LAB-COD',
                'name' => 'Chemical Oxygen Demand (COD)',
                'method' => 'SNI 06-6989.2-2009 (Refluks Tertutup Spektrofotometri)',
                'unit' => 'mg/L',
                'tariff' => 85000.00,
                'standard_reference' => 'Maks. 100 mg/L'
            ],
            [
                'code' => 'LAB-BOD',
                'name' => 'Biochemical Oxygen Demand (BOD5)',
                'method' => 'SNI 6989.72:2009 (Inkubasi 5 Hari 20°C)',
                'unit' => 'mg/L',
                'tariff' => 90000.00,
                'standard_reference' => 'Maks. 30 mg/L'
            ],
            [
                'code' => 'LAB-DO',
                'name' => 'Dissolved Oxygen (DO / Oksigen Terlarut)',
                'method' => 'SNI 06-6989.14-2004 (Winkler / DO Meter)',
                'unit' => 'mg/L',
                'tariff' => 35000.00,
                'standard_reference' => 'Min. 4 mg/L'
            ],
            [
                'code' => 'LAB-NH3',
                'name' => 'Amonia Bebas (NH3-N)',
                'method' => 'SNI 06-6989.30-2005 (Fenol Spektrofotometri)',
                'unit' => 'mg/L',
                'tariff' => 60000.00,
                'standard_reference' => 'Maks. 0.5 mg/L'
            ],
            [
                'code' => 'LAB-TURB',
                'name' => 'Kekeruhan (Turbidity)',
                'method' => 'SNI 06-6989.25-2005 (Turbidimeter Nefelometri)',
                'unit' => 'NTU',
                'tariff' => 30000.00,
                'standard_reference' => 'Maks. 25 NTU'
            ],
            [
                'code' => 'LAB-TSS',
                'name' => 'Total Suspended Solids (TSS)',
                'method' => 'SNI 06-6989.3-2004 (Gravimetri 105°C)',
                'unit' => 'mg/L',
                'tariff' => 55000.00,
                'standard_reference' => 'Maks. 50 mg/L'
            ],
        ];

        foreach ($labParameters as $param) {
            DB::table('dlh_lab_parameters')->updateOrInsert(
                ['code' => $param['code']],
                [
                    'name' => $param['name'],
                    'method' => $param['method'],
                    'unit' => $param['unit'],
                    'tariff' => $param['tariff'],
                    'standard_reference' => $param['standard_reference'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
