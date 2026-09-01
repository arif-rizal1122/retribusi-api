<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TpiWameoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
        $classification = \App\Models\RetributionClassification::find(204);
        if (!$classification) {
            $this->command->warn('Classification ID 204 not found!');
            return;
        }

        $opd_id = $classification->retributionType->opd_id ?? 1;

        // 1. Update Name
        $classification->name = 'TPI Wameo';

        // 2. Prepare Rates from Perda
        $ratesData = [
            ['name' => 'jasa_cold_room', 'amount' => 2000, 'unit' => 'per m2 per Jam', 'label' => 'Jasa Cold Room'],
            ['name' => 'jasa_abf', 'amount' => 1000, 'unit' => 'per Kg per 8 Jam', 'label' => 'Jasa ABF (Air Blast Freezer)'],
            ['name' => 'penjualan_es', 'amount' => 750, 'unit' => 'per Kg', 'label' => 'Penjualan Es'],
            ['name' => 'plugging_container', 'amount' => 50000, 'unit' => 'per RC per 8 Jam', 'label' => 'Plugging Container'],
            ['name' => 'penghancur_es', 'amount' => 50, 'unit' => 'per Kg', 'label' => 'Penghancur Es (Ice Cruiser)'],
            ['name' => 'jasa_meja_sortir', 'amount' => 1000, 'unit' => 'per Meja per Jam', 'label' => 'Jasa Meja Sortir'],
            ['name' => 'jasa_keranjang', 'amount' => 500, 'unit' => 'per Keranjang per Hari', 'label' => 'Jasa Keranjang'],
            ['name' => 'jasa_timbangan', 'amount' => 500, 'unit' => 'per Timbangan per Hari', 'label' => 'Jasa Timbangan'],
            ['name' => 'jasa_gerobak', 'amount' => 2500, 'unit' => 'per Gerobak per Hari', 'label' => 'Jasa Gerobak'],
            ['name' => 'jasa_cuci_ikan', 'amount' => 5000, 'unit' => 'per m2 per Jam', 'label' => 'Jasa Cuci Ikan'],
            ['name' => 'area_pemasaran', 'amount' => 1000, 'unit' => 'per m2 per Jam', 'label' => 'Area Pemasaran / Lelang'],
            ['name' => 'pas_harian_orang', 'amount' => 1000, 'unit' => 'per Orang', 'label' => 'Pas Masuk (Orang)'],
            ['name' => 'pas_harian_sepeda', 'amount' => 1000, 'unit' => 'per Kendaraan', 'label' => 'Pas Masuk (Sepeda)'],
            ['name' => 'pas_harian_r2', 'amount' => 2000, 'unit' => 'per Kendaraan', 'label' => 'Pas Masuk (Roda 2 / Gerobak)'],
            ['name' => 'pas_harian_r3_r4', 'amount' => 3000, 'unit' => 'per Kendaraan', 'label' => 'Pas Masuk (Roda 3 / Roda 4)'],
            ['name' => 'pas_harian_r6', 'amount' => 4000, 'unit' => 'per Kendaraan', 'label' => 'Pas Masuk (Roda 6)'],
            ['name' => 'pas_harian_tronton', 'amount' => 5000, 'unit' => 'per Kendaraan', 'label' => 'Pas Masuk (Tronton / Alat Berat)'],
            ['name' => 'gudang_tertutup', 'amount' => 3000, 'unit' => 'per m2 per Bulan', 'label' => 'Penyimpanan (Gudang Tertutup)'],
            ['name' => 'gudang_terbuka', 'amount' => 1500, 'unit' => 'per m2 per Bulan', 'label' => 'Penyimpanan (Halaman Terbuka)'],
            ['name' => 'air_tpi', 'amount' => 25000, 'unit' => 'per Liter', 'label' => 'Air TPI / Air Sumur Bor'],
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

        $this->command->info('Successfully updated TPI Wameo!');
    }
}
