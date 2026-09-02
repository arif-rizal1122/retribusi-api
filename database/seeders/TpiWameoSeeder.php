<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TpiWameoSeeder extends Seeder
{
    /**
     * Seed klasifikasi JU-PEL (Tempat Pelelangan Ikan / TPI) dengan tarif resmi
     * Perda Kota Baubau No 1 Tahun 2024 Lampiran II bagian b.
     * Sumber: Dokumen (167) — ringkasan fasilitas & tarif TPI.
     * Diperbaiki 2 Sep 2026: angka tarif disesuaikan dengan Perda (sebelumnya keliru).
     */
    public function run()
    {
        $classification = \App\Models\RetributionClassification::find(204);
        if (!$classification) {
            $this->command->warn('Classification ID 204 not found!');
            return;
        }

        $opd_id = $classification->retributionType->opd_id ?? 1;

        // 1. Update Name — konsisten dengan keputusan M-PAD: pelelangan khusus IKAN (TPI)
        $classification->name = 'Tempat Pelelangan Ikan (TPI)';

        // 2. Tarif resmi Perda 1/2024 Lampiran II b — 17 fasilitas utama + 15 tarif tambahan
        $ratesData = [
            // Fasilitas utama
            ['name' => 'tempat_pemasaran_ikan', 'amount' => 500, 'unit' => 'per m2', 'label' => 'Tempat Pemasaran Ikan'],
            ['name' => 'jasa_cold_room', 'amount' => 1500, 'unit' => 'per Kg', 'label' => 'Jasa Cold Room'],
            ['name' => 'jasa_abf', 'amount' => 1000, 'unit' => 'per Kg', 'label' => 'Jasa ABF (Air Blast Freezer)'],
            ['name' => 'penjualan_es', 'amount' => 500, 'unit' => 'per Kg', 'label' => 'Penjualan Es'],
            ['name' => 'plugging_container_20', 'amount' => 300000, 'unit' => 'per RC per 8 Jam', 'label' => 'Plugging Container 20\' Reefer'],
            ['name' => 'plugging_container_40', 'amount' => 450000, 'unit' => 'per RC per 8 Jam', 'label' => 'Plugging Container 40\' Reefer'],
            ['name' => 'penghancur_es', 'amount' => 250, 'unit' => 'per Kg', 'label' => 'Penghancur Es (Ice Cruiser)'],
            ['name' => 'jasa_meja_sortir', 'amount' => 5000, 'unit' => 'per Meja per Jam', 'label' => 'Meja Sortir Ikan'],
            ['name' => 'peti_ikan_1000', 'amount' => 50000, 'unit' => 'per Unit per Hari', 'label' => 'Peti Ikan (Cold Box) 1000 Liter'],
            ['name' => 'peti_ikan_300', 'amount' => 20000, 'unit' => 'per Unit per Hari', 'label' => 'Peti Ikan (Cold Box) 300 Liter'],
            ['name' => 'peti_ikan_200', 'amount' => 13500, 'unit' => 'per Unit per Hari', 'label' => 'Peti Ikan (Cold Box) 200 Liter'],
            ['name' => 'jasa_keranjang', 'amount' => 4500, 'unit' => 'per Unit per Hari', 'label' => 'Keranjang Plastik (Trays)'],
            ['name' => 'timbangan_pegas', 'amount' => 7000, 'unit' => 'per Unit per Jam', 'label' => 'Timbangan Ikan Pegas'],
            ['name' => 'timbangan_digital', 'amount' => 10000, 'unit' => 'per Unit per Jam', 'label' => 'Timbangan Ikan Digital'],
            ['name' => 'jasa_forklift', 'amount' => 135000, 'unit' => 'per Unit per Jam', 'label' => 'Forklift'],
            ['name' => 'listrik_reefer_20', 'amount' => 200000, 'unit' => 'per Jam', 'label' => 'Listrik Mobile Reefer 20\''],
            ['name' => 'listrik_reefer_40', 'amount' => 360000, 'unit' => 'per Jam', 'label' => 'Listrik Mobile Reefer 40\''],
            // Tarif tambahan kawasan TPI
            ['name' => 'pas_harian_orang', 'amount' => 1000, 'unit' => 'per Orang per sekali masuk', 'label' => 'Pas Harian Masuk Kawasan (Orang)'],
            ['name' => 'pas_harian_r2_r3', 'amount' => 2000, 'unit' => 'per Unit per sekali masuk', 'label' => 'Pas Harian Kendaraan Roda 2/3'],
            ['name' => 'pas_harian_r4', 'amount' => 6000, 'unit' => 'per Unit per sekali masuk', 'label' => 'Pas Harian Kendaraan Roda 4'],
            ['name' => 'pas_harian_truck', 'amount' => 10000, 'unit' => 'per Unit per sekali masuk', 'label' => 'Pas Harian Truck'],
            ['name' => 'pas_harian_tronton', 'amount' => 15000, 'unit' => 'per Unit per sekali masuk', 'label' => 'Pas Harian Tronton'],
            ['name' => 'pas_langganan_r2_r3', 'amount' => 30000, 'unit' => 'per Unit per Bulan', 'label' => 'Pas Berlangganan Roda 2/3'],
            ['name' => 'pas_langganan_r4', 'amount' => 90000, 'unit' => 'per Unit per Bulan', 'label' => 'Pas Berlangganan Roda 4'],
            ['name' => 'penyimpanan_halaman', 'amount' => 1000, 'unit' => 'per m2 per Hari', 'label' => 'Jasa Penyimpanan Halaman (tanpa atap)'],
            ['name' => 'penyimpanan_beratap', 'amount' => 1500, 'unit' => 'per m2 per Hari', 'label' => 'Jasa Penyimpanan Bangunan Beratap (tanpa dinding)'],
            ['name' => 'gudang_administrasi', 'amount' => 10000, 'unit' => 'per m2 per Bulan', 'label' => 'Gudang Administrasi/Kantor'],
            ['name' => 'gudang_kios_maritim', 'amount' => 8000, 'unit' => 'per m2 per Bulan', 'label' => 'Gudang Kios Maritim'],
            ['name' => 'gudang_penyimpanan', 'amount' => 7000, 'unit' => 'per m2 per Bulan', 'label' => 'Gudang Penyimpanan/Pengolahan/Pemasaran'],
            ['name' => 'gudang_pengepakan', 'amount' => 7000, 'unit' => 'per m2 per Bulan', 'label' => 'Gudang Tempat Pengepakan Ikan'],
            ['name' => 'air_pipa_dermaga', 'amount' => 20, 'unit' => 'per Liter', 'label' => 'Pengadaan Air TPI (pipa dermaga)'],
            ['name' => 'air_transportasi', 'amount' => 100, 'unit' => 'per Liter', 'label' => 'Pengadaan Air TPI (alat transportasi lain)'],
        ];

        \App\Models\RetributionRate::where('retribution_classification_id', 204)->delete();

        $options = [];
        foreach ($ratesData as $rateItem) {
            \App\Models\RetributionRate::create([
                'opd_id' => $opd_id,
                'retribution_type_id' => $classification->retribution_type_id,
                'retribution_classification_id' => 204,
                'name' => $rateItem['name'],
                'amount' => $rateItem['amount'],
                'unit' => $rateItem['unit'],
                'is_active' => true,
            ]);

            $options[] = [
                'value' => $rateItem['name'],
                'label' => $rateItem['label'] . ' (' . $rateItem['unit'] . ')',
                'rates' => [
                    $rateItem['name'] => $rateItem['amount']
                ]
            ];
        }

        // 3. Update form_schema
        $newSchema = [
            [
                'key' => 'nama_pengguna',
                'type' => 'text',
                'label' => 'Nama Pemohon / Pengguna Jasa',
                'required' => true
            ],
            [
                'key' => 'jenis_layanan',
                'type' => 'select',
                'label' => 'Jenis Fasilitas / Layanan',
                'required' => true,
                'options' => $options
            ],
            [
                'key' => 'kuantitas',
                'type' => 'number',
                'label' => 'Kuantitas (Sesuai Satuan Volume/Berat/Area)',
                'required' => true
            ],
            [
                'key' => 'durasi',
                'type' => 'number',
                'label' => 'Durasi (Jam/Hari/Bulan) - Kosongkan jika tidak berlaku',
                'required' => false
            ]
        ];

        $classification->form_schema = $newSchema;
        $classification->calculation_formula = 'kuantitas * rate_amount * (durasi > 0 ? durasi : 1)';
        $classification->save();

        $this->command->info('Successfully updated TPI (Tempat Pelelangan Ikan) with Perda 1/2024 rates!');
    }
}
