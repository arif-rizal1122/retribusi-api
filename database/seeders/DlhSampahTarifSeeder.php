<?php

namespace Database\Seeders;

use App\Models\Dlh\DlhSampahTarifKategori;
use Illuminate\Database\Seeder;

class DlhSampahTarifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Basis: Perda Kota Baubau No. 1 Tahun 2024 tentang Pajak Daerah & Retribusi Daerah
     */
    public function run(): void
    {
        $categories = [
            [
                'kode_tarif' => 'SMP-01',
                'nama_kategori' => 'Rumah Tinggal Sederhana (< Tipe 36)',
                'kelompok' => 'rumah_tangga',
                'tarif_bulanan' => 8500.00,
                'keterangan' => 'Tarif bersubsidi Pemkot untuk hunian keluarga sederhana',
                'is_active' => true,
            ],
            [
                'kode_tarif' => 'SMP-02',
                'nama_kategori' => 'Rumah Tinggal Menengah / Mewah',
                'kelompok' => 'rumah_tangga',
                'tarif_bulanan' => 15000.00,
                'keterangan' => 'Tarif standar kawasan permukiman kota Baubau',
                'is_active' => true,
            ],
            [
                'kode_tarif' => 'SMP-03',
                'nama_kategori' => 'Ruko / Usaha Dagang / Jasa Komersial',
                'kelompok' => 'bisnis_niaga',
                'tarif_bulanan' => 22000.00,
                'keterangan' => 'Tarif ruko dan tempat usaha komersial',
                'is_active' => true,
            ],
            [
                'kode_tarif' => 'SMP-04',
                'nama_kategori' => 'Restoran / Rumah Makan / Kafe',
                'kelompok' => 'bisnis_niaga',
                'tarif_bulanan' => 45000.00,
                'keterangan' => 'Tarif usaha kuliner dengan volume sampah menengah-tinggi',
                'is_active' => true,
            ],
            [
                'kode_tarif' => 'SMP-05',
                'nama_kategori' => 'Kios / Los di Lingkungan Pasar',
                'kelompok' => 'bisnis_niaga',
                'tarif_bulanan' => 22000.00,
                'keterangan' => 'Tarif kios/los permanen persampahan pasar',
                'is_active' => true,
            ],
            [
                'kode_tarif' => 'SMP-06',
                'nama_kategori' => 'Pergudangan / Industri / Hotel',
                'kelompok' => 'bisnis_niaga',
                'tarif_bulanan' => 50000.00,
                'keterangan' => 'Tarif persampahan kawasan industri, hotel, dan gudang',
                'is_active' => true,
            ],
            [
                'kode_tarif' => 'SMP-07',
                'nama_kategori' => 'Sosial / Rumah Ibadah / Panti',
                'kelompok' => 'sosial_khusus',
                'tarif_bulanan' => 0.00,
                'keterangan' => 'Pembebasan retribusi sampah untuk fungsi sosial dan keagamaan',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $cat) {
            DlhSampahTarifKategori::updateOrCreate(
                ['kode_tarif' => $cat['kode_tarif']],
                $cat
            );
        }
    }
}
