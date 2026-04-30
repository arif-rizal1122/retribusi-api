<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RetributionClassification;

class ClassificationSchemaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. PBB-P2
        $this->updateSchema('PBB-P2', [
            ['key' => 'luas_bumi', 'label' => 'Luas Bumi (m2)', 'type' => 'number', 'required' => true],
            ['key' => 'luas_bangunan', 'label' => 'Luas Bangunan (m2)', 'type' => 'number', 'required' => true],
            ['key' => 'njop_bumi', 'label' => 'NJOP Bumi per m2 (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'njop_bangunan', 'label' => 'NJOP Bangunan per m2 (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'njoptkp', 'label' => 'NJOPTKP (Rp)', 'type' => 'number', 'default_value' => 10000000],
        ], [
            ['key' => 'ktp_pemilik', 'name' => 'KTP Pemilik', 'required' => true],
            ['key' => 'sertifikat_tanah', 'name' => 'Sertifikat Tanah / Akta Jual Beli', 'required' => true],
            ['key' => 'foto_objek', 'name' => 'Foto Objek Pajak', 'required' => true],
        ]);

        // 2. PBJT Makan dan Minum (Restaurant)
        $this->updateSchema('PBJT - Makan dan Minum', [
            ['key' => 'omset_bulanan', 'label' => 'Total Omzet Bulanan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'jumlah_meja', 'label' => 'Jumlah Meja', 'type' => 'number'],
            ['key' => 'kapasitas_kursi', 'label' => 'Total Kapasitas Kursi', 'type' => 'number'],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
            ['key' => 'foto_tempat_usaha', 'name' => 'Foto Tempat Usaha', 'required' => true],
        ]);

        // 3. PBJT Jasa Perhotelan
        $this->updateSchema('PBJT - Jasa Perhotelan', [
            ['key' => 'omset_kamar', 'label' => 'Omzet Pemakaian Kamar (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'omset_lain', 'label' => 'Omzet Lain-lain (Rp)', 'type' => 'number'],
            ['key' => 'jumlah_kamar', 'label' => 'Total Jumlah Kamar', 'type' => 'number', 'required' => true],
            ['key' => 'tipe_hotel', 'label' => 'Tipe Hotel', 'type' => 'select', 'options' => [
                ['label' => 'Bintang 5', 'value' => 'bintang_5'],
                ['label' => 'Bintang 4', 'value' => 'bintang_4'],
                ['label' => 'Bintang 3', 'value' => 'bintang_3'],
                ['label' => 'Melati / Guest House', 'value' => 'melati'],
            ]],
        ], [
            ['key' => 'izin_tdup', 'name' => 'Izin TDUP', 'required' => true],
            ['key' => 'foto_bangunan_hotel', 'name' => 'Foto Bangunan Hotel', 'required' => true],
        ]);

        // 4. Pajak Reklame
        $this->updateSchema('Pajak Reklame', [
            ['key' => 'panjang', 'label' => 'Panjang Reklame (m)', 'type' => 'number', 'required' => true],
            ['key' => 'lebar', 'label' => 'Lebar Reklame (m)', 'type' => 'number', 'required' => true],
            ['key' => 'jumlah_sisi', 'label' => 'Jumlah Sisi', 'type' => 'number', 'default_value' => 1],
            ['key' => 'jumlah_titik', 'label' => 'Jumlah Titik/Unit', 'type' => 'number', 'default_value' => 1],
            ['key' => 'durasi_hari', 'label' => 'Durasi Penayangan (Hari)', 'type' => 'number', 'default_value' => 365],
            ['key' => 'jenis_reklame', 'label' => 'Jenis Reklame', 'type' => 'select', 'options' => [
                ['label' => 'Billboard', 'value' => 'billboard'],
                ['label' => 'Neon Box', 'value' => 'neon_box'],
                ['label' => 'Spanduk', 'value' => 'spanduk'],
                ['label' => 'Baliho', 'value' => 'baliho'],
            ]],
        ], [
            ['key' => 'desain_reklame', 'name' => 'Desain Reklame', 'required' => true],
            ['key' => 'ipr_dokumen', 'name' => 'Izin Penyelenggaraan Reklame (IPR)', 'required' => true],
            ['key' => 'foto_titik_reklame', 'name' => 'Foto Lokasi Titik Reklame', 'required' => true],
        ]);

        // 5. MBLB
        $this->updateSchema('Pajak MBLB', [
            ['key' => 'volume_m3', 'label' => 'Volume Pengambilan (m3)', 'type' => 'number', 'required' => true],
            ['key' => 'jenis_material', 'label' => 'Jenis Material', 'type' => 'select', 'options' => [
                ['label' => 'Pasir', 'value' => 'pasir'],
                ['label' => 'Batu', 'value' => 'batu'],
                ['label' => 'Tanah Timbun', 'value' => 'tanah'],
            ]],
        ], [
            ['key' => 'izin_sipb', 'name' => 'Izin SIPB / IUP', 'required' => true],
            ['key' => 'laporan_produksi', 'name' => 'Laporan Produksi Bulanan', 'required' => true],
        ]);

        // 7. PBJT Jasa Kesenian dan Hiburan
        $this->updateSchema('PBJT - Jasa Kesenian dan Hiburan', [
            ['key' => 'omset_bulanan', 'label' => 'Total Omzet Bulanan (Rp)', 'type' => 'number', 'required' => true],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
            ['key' => 'foto_tempat_hiburan', 'name' => 'Foto Tempat Hiburan', 'required' => true],
        ]);

        // 8. PBJT Tenaga Listrik
        $this->updateSchema('PBJT - Tenaga Listrik', [
            ['key' => 'tagihan', 'label' => 'Nilai Tagihan Listrik (Rp)', 'type' => 'number', 'required' => true],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
        ]);

        // 9. PBJT Jasa Parkir
        $this->updateSchema('PBJT - Jasa Parkir', [
            ['key' => 'omset_bulanan', 'label' => 'Total Omzet Bulanan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'luas_parkir', 'label' => 'Luas Lahan Parkir (m2)', 'type' => 'number'],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
            ['key' => 'foto_lahan_parkir', 'name' => 'Foto Lahan Parkir', 'required' => true],
        ]);

        // 10. Pajak Air Bawah Tanah
        $this->updateSchema('Air Tanah', [
            ['key' => 'volume_m3', 'label' => 'Volume Pengambilan (m3)', 'type' => 'number', 'required' => true],
            ['key' => 'nilai_perolehan_air', 'label' => 'Nilai Perolehan Air (NPA)', 'type' => 'number'],
        ], [
            ['key' => 'izin_sipa', 'name' => 'Izin SIPA', 'required' => true],
            ['key' => 'foto_meteran', 'name' => 'Foto Meteran Air', 'required' => true],
        ]);

        // 11. Pajak Sarang Burung Walet
        $this->updateSchema('Pajak Sarang Burung Walet', [
            ['key' => 'omset_bulanan', 'label' => 'Total Omzet Bulanan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'hasil_panen_kg', 'label' => 'Hasil Panen (kg)', 'type' => 'number'],
        ], [
            ['key' => 'foto_lokasi', 'name' => 'Foto Lokasi Walet', 'required' => true],
        ]);
        
        // 6. Retribusi Pasar (Penyediaan Tempat Kegiatan Usaha)
        $this->updateSchema('Penyediaan Tempat Kegiatan Usaha', [
            ['key' => 'luas_kios', 'label' => 'Luas Kios (m2)', 'type' => 'number', 'required' => true],
            ['key' => 'blok_pasar', 'label' => 'Blok/Nomor Kios', 'type' => 'text'],
        ], [
            ['key' => 'sertifikat_kios', 'name' => 'Sertifikat Pemakaian Kios', 'required' => true],
            ['key' => 'ktp_pedagang', 'name' => 'KTP Pedagang', 'required' => true],
        ]);
    }

    private function updateSchema($name, $schema, $requirements)
    {
        $classification = RetributionClassification::where('name', 'like', '%' . $name . '%')->first();
        if ($classification) {
            $classification->update([
                'form_schema' => $schema,
                'requirements' => $requirements
            ]);
            $this->command->info("Updated schema for: " . $classification->name);
        } else {
            $this->command->warn("Classification not found: " . $name);
        }
    }
}
