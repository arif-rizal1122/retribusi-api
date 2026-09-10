<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\RetributionClassification;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $classification = RetributionClassification::find(196) 
            ?? RetributionClassification::where('code', 'PBJT-PLN')->first();

        if ($classification) {
            $classification->update([
                'name' => 'PBJT - Tenaga Listrik (Non-PLN / Dihasilkan Sendiri)',
                'code' => 'PBJT-NON-PLN',
                'description' => 'PBJT Tenaga Listrik (Tarif 1,5%) khusus konsumsi tenaga listrik yang dihasilkan sendiri (Genset / Pembangkit Mandiri kapasitas total >= 200 kVA atau berizin operasi) sesuai Pasal 23 ayat (2) huruf c jo. Pasal 27 ayat (3) huruf b Perda Kota Baubau No. 1 Tahun 2024. Tenaga listrik yang bersumber dari PLN tidak dilaporkan mandiri di sini karena PBJT-nya telah dipungut dan disetorkan langsung oleh PT PLN (Persero) ke Kas Daerah.',
                'form_schema' => [
                    [
                        'key' => 'jenis_pembangkit',
                        'label' => 'Jenis Pembangkit Listrik Mandiri',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            ['label' => 'Genset Diesel / Solar (Industri / Usaha)', 'value' => 'genset_diesel'],
                            ['label' => 'Pembangkit Listrik Tenaga Surya (PLTS) Mandiri', 'value' => 'plts'],
                            ['label' => 'Turbin / Pembangkit Lainnya', 'value' => 'lainnya'],
                        ],
                        'helper' => 'Pilih jenis sumber pembangkitan tenaga listrik mandiri',
                    ],
                    [
                        'key' => 'kapasitas_daya_kva',
                        'label' => 'Kapasitas Daya Pembangkit (kVA / kW)',
                        'type' => 'number',
                        'required' => true,
                        'helper' => 'Kapasitas total genset / pembangkit mandiri (kVA)',
                    ],
                    [
                        'key' => 'jam_nyala_sebulan',
                        'label' => 'Rata-rata Jam Nyala / Pengoperasian per Bulan (Jam)',
                        'type' => 'number',
                        'required' => true,
                        'helper' => 'Estimasi jam nyala operasional genset dalam 1 bulan',
                    ],
                    [
                        'key' => 'estimasi_kwh_bulanan',
                        'label' => 'Total Estimasi Produksi Listrik Mandiri (kWh)',
                        'type' => 'number',
                        'required' => true,
                        'helper' => 'Kapasitas (kW) x Jam Nyala x Faktor Beban operasional',
                    ],
                    [
                        'key' => 'harga_satuan_njtl',
                        'label' => 'Harga Satuan Listrik Acuan NJTL (Rp/kWh)',
                        'type' => 'number',
                        'required' => true,
                        'default_value' => 1444,
                        'helper' => 'Tarif acuan Nilai Jual Tenaga Listrik (Rp 1.444 / kWh)',
                    ],
                    [
                        'key' => 'nomor_slo_atau_iuptl',
                        'label' => 'Nomor Sertifikat Laik Operasi (SLO) / IUPTLS',
                        'type' => 'text',
                        'required' => false,
                        'helper' => 'Nomor SLO / Izin Operasi Pembangkit Mandiri (jika ada)',
                    ],
                ],
                'requirements' => [
                    [
                        'key' => 'foto_nameplate',
                        'name' => 'Foto Nameplate Spesifikasi Genset / Pembangkit',
                        'required' => true,
                        'label' => 'Foto Nameplate Spesifikasi Genset / Pembangkit',
                    ],
                    [
                        'key' => 'dokumen_slo',
                        'name' => 'Dokumen Sertifikat Laik Operasi (SLO) / IUPTLS',
                        'required' => false,
                        'label' => 'Dokumen Sertifikat Laik Operasi (SLO) / IUPTLS',
                    ],
                    [
                        'key' => 'logbook_operasional',
                        'name' => 'Catatan Jam Nyala / Logbook Pembelian Bahan Bakar',
                        'required' => false,
                        'label' => 'Catatan Jam Nyala / Logbook Pembelian Bahan Bakar',
                    ],
                ],
                'calculation_formula' => '(estimasi_kwh_bulanan * harga_satuan_njtl) * 0.015',
                'is_self_assessment' => true,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $classification = RetributionClassification::find(196)
            ?? RetributionClassification::where('code', 'PBJT-NON-PLN')->first();

        if ($classification) {
            $classification->update([
                'name' => 'PBJT - Tenaga Listrik',
                'code' => 'PBJT-PLN',
                'description' => 'PBJT Tenaga Listrik (10%)',
                'calculation_formula' => 'tagihan * 0.1',
            ]);
        }
    }
};
