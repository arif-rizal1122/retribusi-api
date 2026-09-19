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
            ['key' => 'kategori_layanan', 'label' => 'Kategori Usaha', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Restoran / Rumah Makan', 'value' => 'restoran'],
                ['label' => 'Kafe / Kedai Kopi', 'value' => 'kafe'],
                ['label' => 'Katering / Jasa Boga', 'value' => 'katering'],
                ['label' => 'Bakery / Toko Roti & Kue', 'value' => 'bakery'],
                ['label' => 'Warung Kuliner', 'value' => 'warung'],
            ]],
            ['key' => 'omset', 'label' => 'Estimasi Omzet per Bulan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'kapasitas_kursi', 'label' => 'Kapasitas Tempat Duduk (Kursi)', 'type' => 'number'],
            ['key' => 'kapasitas_meja', 'label' => 'Kapasitas Meja', 'type' => 'number'],
            ['key' => 'jumlah_karyawan', 'label' => 'Jumlah Karyawan', 'type' => 'number'],
            ['key' => 'jam_operasional', 'label' => 'Jam Operasional (Contoh: 09:00 - 22:00)', 'type' => 'text'],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
            ['key' => 'daftar_menu', 'name' => 'Daftar Menu & Price List', 'required' => true],
            ['key' => 'foto_tempat_usaha', 'name' => 'Foto Fasad & Area Usaha', 'required' => true],
        ]);

        // 3. PBJT Jasa Perhotelan
        $this->updateSchema('PBJT - Jasa Perhotelan', [
            ['key' => 'klasifikasi_hotel', 'label' => 'Klasifikasi Jasa Perhotelan', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Hotel Bintang 5', 'value' => 'bintang_5'],
                ['label' => 'Hotel Bintang 4', 'value' => 'bintang_4'],
                ['label' => 'Hotel Bintang 3', 'value' => 'bintang_3'],
                ['label' => 'Hotel Bintang 1 & 2', 'value' => 'bintang_1_2'],
                ['label' => 'Hotel Melati / Non-Bintang', 'value' => 'melati'],
                ['label' => 'Guest House / Homestay / Vila', 'value' => 'homestay'],
                ['label' => 'Rumah Kos (>= 10 Kamar)', 'value' => 'kost'],
            ]],
            ['key' => 'omset', 'label' => 'Estimasi Omzet per Bulan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'jumlah_kamar', 'label' => 'Jumlah Total Kamar', 'type' => 'number', 'required' => true],
            ['key' => 'tarif_kamar_min', 'label' => 'Tarif Kamar Terendah (Rp / Malam)', 'type' => 'number'],
            ['key' => 'tarif_kamar_max', 'label' => 'Tarif Kamar Tertinggi (Rp / Malam)', 'type' => 'number'],
            ['key' => 'occupancy_rate', 'label' => 'Estimasi Okupansi Rata-rata (%)', 'type' => 'number'],
        ], [
            ['key' => 'izin_tdup', 'name' => 'NIB / TDUP Hotel', 'required' => true],
            ['key' => 'foto_bangunan_hotel', 'name' => 'Foto Bangunan & Fasilitas Hotel', 'required' => true],
        ]);

        // 4. Pajak Reklame
        $this->updateSchema('Pajak Reklame', [
            ['key' => 'kategori_reklame', 'label' => 'Kategori Reklame', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Megatron / Videotron / LED', 'value' => 'videotron'],
                ['label' => 'Billboard / Bando Jalan', 'value' => 'billboard'],
                ['label' => 'Neon Box / Sign Board', 'value' => 'neon_box'],
                ['label' => 'Baliho / Banner Rangka', 'value' => 'baliho'],
                ['label' => 'Spanduk / Umbul-umbul Kain', 'value' => 'spanduk'],
                ['label' => 'Reklame Kendaraan / Berjalan', 'value' => 'kendaraan'],
            ]],
            ['key' => 'judul_reklame', 'label' => 'Naskah / Tema Reklame', 'type' => 'text', 'required' => true],
            ['key' => 'panjang', 'label' => 'Panjang Media (Meter)', 'type' => 'number', 'required' => true],
            ['key' => 'lebar', 'label' => 'Lebar Media (Meter)', 'type' => 'number', 'required' => true],
            ['key' => 'sisi', 'label' => 'Muka / Sisi', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => '1 Sisi (Tunggal)', 'value' => '1'],
                ['label' => '2 Sisi (Bolak-Balik)', 'value' => '2'],
                ['label' => '3 Sisi (Segitiga)', 'value' => '3'],
            ]],
            ['key' => 'luas', 'label' => 'Luas Total (m2)', 'type' => 'number', 'required' => true, 'readonly' => true, 'helper' => 'Otomatis dihitung: Panjang × Lebar × Sisi'],
            ['key' => 'masa_tayang_mulai', 'label' => 'Masa Tayang Mulai', 'type' => 'date', 'required' => true],
            ['key' => 'masa_tayang_selesai', 'label' => 'Masa Tayang Selesai', 'type' => 'date', 'required' => true],
        ], [
            ['key' => 'materi_reklame', 'name' => 'Desain / Materi Reklame', 'required' => true],
            ['key' => 'foto_titik', 'name' => 'Foto Titik Pemasangan', 'required' => true],
        ]);

        // 5. MBLB (Mineral Bukan Logam dan Batuan) — Objek adalah pengambilan material, bukan izin
        $this->updateSchema('Pajak MBLB', [
            ['key' => 'jenis_mineral', 'label' => 'Jenis Komoditas Mineral', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Batu Gamping (Kapur)', 'value' => 'gamping'],
                ['label' => 'Pasir Kuarsa / Pasir Pasang', 'value' => 'pasir'],
                ['label' => 'Tanah Urug / Timbunan', 'value' => 'tanah_urug'],
                ['label' => 'Tanah Liat', 'value' => 'tanah_liat'],
                ['label' => 'Kerikil / Sirtu', 'value' => 'sirtu'],
                ['label' => 'Batu Andesit / Belah', 'value' => 'andesit'],
            ]],
            ['key' => 'volume', 'label' => 'Estimasi Volume per Bulan', 'type' => 'number', 'required' => true],
            ['key' => 'satuan_volume', 'label' => 'Satuan', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Meter Kubik (m3)', 'value' => 'm3'],
                ['label' => 'Ton', 'value' => 'ton'],
            ]],
            ['key' => 'nomor_izin', 'label' => 'Nomor IUP / SIPB', 'type' => 'text', 'required' => false, 'helper' => 'Opsional jika ada'],
            ['key' => 'tanggal_izin', 'label' => 'Masa Berlaku Izin', 'type' => 'date', 'required' => false, 'helper' => 'Opsional jika ada'],
            ['key' => 'luas_wip', 'label' => 'Luas Wilayah Tambang (Ha)', 'type' => 'number', 'required' => false, 'helper' => 'Opsional jika ada'],
        ], [
            ['key' => 'foto_titik', 'name' => 'Foto Lokasi Penambangan / Titik Galian', 'required' => true],
            ['key' => 'dokumen_iup', 'name' => 'Salinan Dokumen IUP / SIPB (Jika Ada)', 'required' => false],
        ]);

        // 6. Pajak Air Tanah
        $this->updateSchema('Air Tanah', [
            ['key' => 'tujuan_pengambilan', 'label' => 'Tujuan Pengambilan Air', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Niaga Kecil (Rumah Makan / Ruko / Bengkel)', 'value' => 'niaga_kecil'],
                ['label' => 'Niaga Besar (Hotel / Mal / Sarana Hiburan)', 'value' => 'niaga_besar'],
                ['label' => 'Industri / Pabrik', 'value' => 'industri'],
                ['label' => 'Pariwisata / Kolam Renang', 'value' => 'wisata'],
            ]],
            ['key' => 'jenis_sumur', 'label' => 'Jenis Sumur', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Sumur Bor / Artesis', 'value' => 'sumur_bor'],
                ['label' => 'Sumur Gali / Dangkal', 'value' => 'sumur_gali'],
            ]],
            ['key' => 'kedalaman_sumur', 'label' => 'Kedalaman Sumur (Meter)', 'type' => 'number', 'required' => true],
            ['key' => 'volume', 'label' => 'Estimasi Pemakaian per Bulan (m3)', 'type' => 'number', 'required' => true],
            ['key' => 'status_meter', 'label' => 'Status Water Meter', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Berfungsi Baik', 'value' => 'baik'],
                ['label' => 'Rusak / Dalam Perbaikan', 'value' => 'rusak'],
                ['label' => 'Belum Terpasang Meteran', 'value' => 'tanpa_meter'],
            ]],
        ], [
            ['key' => 'izin_sipa', 'name' => 'Izin SIPA (Jika Ada)', 'required' => false],
            ['key' => 'foto_sumur', 'name' => 'Foto Titik Sumur & Meteran Air', 'required' => true],
        ]);

        // 7. Pajak Sarang Burung Walet
        $this->updateSchema('Pajak Sarang Burung Walet', [
            ['key' => 'tahun_berdiri', 'label' => 'Tahun Mulai Beroperasi', 'type' => 'number', 'required' => true],
            ['key' => 'luas_bangunan', 'label' => 'Luas Bangunan Walet (m2)', 'type' => 'number', 'required' => true],
            ['key' => 'jumlah_lantai', 'label' => 'Jumlah Lantai Bangunan', 'type' => 'number'],
            ['key' => 'siklus_panen', 'label' => 'Siklus Panen', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => '1 Bulan Sekali', 'value' => '1_bulan'],
                ['label' => '2 Bulan Sekali', 'value' => '2_bulan'],
                ['label' => '3 Bulan Sekali', 'value' => '3_bulan'],
                ['label' => '4 Bulan Sekali', 'value' => '4_bulan'],
            ]],
            ['key' => 'volume', 'label' => 'Perkiraan Hasil Panen per Siklus (Kg)', 'type' => 'number', 'required' => true],
            ['key' => 'harga', 'label' => 'Harga Pasar Berlaku (Rp / Kg)', 'type' => 'number', 'required' => true],
            ['key' => 'penjualan', 'label' => 'Estimasi Omzet Panen (Rp)', 'type' => 'number', 'readonly' => true, 'helper' => 'Otomatis dihitung: Volume × Harga'],
        ], [
            ['key' => 'foto_bangunan', 'name' => 'Foto Gedung Walet', 'required' => true],
        ]);

        // 8. PBJT Jasa Kesenian dan Hiburan
        $this->updateSchema('PBJT - Jasa Kesenian dan Hiburan', [
            ['key' => 'kategori_hiburan', 'label' => 'Kategori Hiburan', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Bioskop / Pemutaran Film', 'value' => 'bioskop'],
                ['label' => 'Pertunjukan Seni / Musik / Tari', 'value' => 'seni_musik'],
                ['label' => 'Pusat Kebugaran / Fitness / Gym', 'value' => 'gym'],
                ['label' => 'Permainan Ketangkasan / Billiard / Bowling', 'value' => 'billiard'],
                ['label' => 'Panti Pijat / Refleksi / Spa', 'value' => 'spa'],
                ['label' => 'Karaoke Keluarga', 'value' => 'karaoke'],
            ]],
            ['key' => 'omset', 'label' => 'Estimasi Omzet Bulanan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'kapasitas_pengunjung', 'label' => 'Kapasitas Pengunjung (Orang)', 'type' => 'number'],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
            ['key' => 'foto_tempat_hiburan', 'name' => 'Foto Tempat Hiburan', 'required' => true],
        ]);

        // 9. PBJT Tenaga Listrik
        $this->updateSchema('PBJT - Tenaga Listrik', [
            ['key' => 'sumber_listrik', 'label' => 'Sumber Tenaga Listrik', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'PLN', 'value' => 'pln'],
                ['label' => 'Non-PLN (Genset / Pembangkit Sendiri)', 'value' => 'non_pln'],
            ]],
            ['key' => 'id_pelanggan', 'label' => 'ID Pelanggan / Nomor Kontrak (PLN)', 'type' => 'text'],
            ['key' => 'daya_terpasang', 'label' => 'Kapasitas Daya (VA / kVA)', 'type' => 'number', 'required' => true],
            ['key' => 'tagihan', 'label' => 'Nilai Tagihan Listrik per Bulan (Rp)', 'type' => 'number', 'required' => true],
        ], [
            ['key' => 'rekening_listrik', 'name' => 'Salinan Rekening Listrik', 'required' => false],
        ]);

        // 10. PBJT Jasa Parkir
        $this->updateSchema('PBJT - Jasa Parkir', [
            ['key' => 'omset', 'label' => 'Total Omzet Parkir Bulanan (Rp)', 'type' => 'number', 'required' => true],
            ['key' => 'luas_parkir', 'label' => 'Luas Lahan Parkir (m2)', 'type' => 'number'],
            ['key' => 'kapasitas_kendaraan', 'label' => 'Kapasitas Kendaraan (Unit)', 'type' => 'number'],
        ], [
            ['key' => 'nib_usaha', 'name' => 'NIB / Izin Usaha', 'required' => true],
            ['key' => 'foto_lahan_parkir', 'name' => 'Foto Lahan Parkir', 'required' => true],
        ]);

        // 11. Retribusi Persampahan
        $this->updateSchema('Persampahan', [
            ['key' => 'kategori_objek', 'label' => 'Kategori Penghasil Sampah', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Rumah Tinggal Sederhana (Rp 10.000/bln)', 'value' => 'rumah_sederhana'],
                ['label' => 'Rumah Tinggal Menengah (Rp 20.000/bln)', 'value' => 'rumah_menengah'],
                ['label' => 'Rumah Mewah / Bertingkat (Rp 35.000/bln)', 'value' => 'rumah_mewah'],
                ['label' => 'Kios / Warung / Toko Kecil (Rp 25.000/bln)', 'value' => 'toko_kecil'],
                ['label' => 'Restoran / Rumah Makan (Rp 50.000/bln)', 'value' => 'restoran'],
                ['label' => 'Hotel / Penginapan (Rp 100.000/bln)', 'value' => 'hotel'],
                ['label' => 'Perkantoran / Instansi (Rp 75.000/bln)', 'value' => 'kantor'],
                ['label' => 'Industri / Pabrik / Gudang (Rp 150.000/bln)', 'value' => 'industri'],
            ]],
            ['key' => 'volume_estimasi', 'label' => 'Perkiraan Volume Sampah Harian', 'type' => 'select', 'options' => [
                ['label' => '< 0.5 m3 per hari', 'value' => 'kecil'],
                ['label' => '0.5 - 1.5 m3 per hari', 'value' => 'sedang'],
                ['label' => '> 1.5 m3 per hari', 'value' => 'besar'],
            ]],
        ], [
            ['key' => 'foto_lokasi', 'name' => 'Foto Tempat / Bangunan', 'required' => true],
        ]);

        // 12. Retribusi PKD / Sewa Lapak Pasar
        $this->updateSchema('PKD', [
            ['key' => 'nama_pasar', 'label' => 'Nama Pasar', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Pasar Wameo', 'value' => 'Pasar Wameo'],
                ['label' => 'Pasar Karya Nugraha', 'value' => 'Pasar Karya Nugraha'],
                ['label' => 'Pasar Laelangi', 'value' => 'Pasar Laelangi'],
                ['label' => 'Pasar Moko', 'value' => 'Pasar Moko'],
                ['label' => 'Pasar Baruga', 'value' => 'Pasar Baruga'],
                ['label' => 'Pasar Modern Ngkari-ngkari', 'value' => 'Pasar Modern Ngkari-ngkari'],
            ]],
            ['key' => 'blok_kios', 'label' => 'Blok Kios / Los', 'type' => 'text', 'required' => true],
            ['key' => 'nomor_kios', 'label' => 'Nomor Kios / Lapak', 'type' => 'text', 'required' => true],
            ['key' => 'jenis_dagangan', 'label' => 'Jenis Komoditas / Dagangan', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Sembako & Pangan', 'value' => 'sembako'],
                ['label' => 'Pakaian & Tekstil', 'value' => 'pakaian'],
                ['label' => 'Sayur, Buah & Bumbu', 'value' => 'sayur_buah'],
                ['label' => 'Ikan, Daging & Unggas', 'value' => 'ikan_daging'],
                ['label' => 'Kuliner & Makanan Jadi', 'value' => 'kuliner'],
                ['label' => 'Emas & Aksesoris', 'value' => 'emas'],
                ['label' => 'Elektronik & Perabotan', 'value' => 'elektronik'],
            ]],
            ['key' => 'luas_kios', 'label' => 'Luas Kios (m2)', 'type' => 'number', 'required' => true],
        ], [
            ['key' => 'ktp_pedagang', 'name' => 'KTP Pedagang', 'required' => true],
            ['key' => 'foto_kios', 'name' => 'Foto Kios / Lapak', 'required' => true],
        ]);

        // 13. Retribusi Pelayanan Pasar (ID 202 / RET-PSR) — Perda 1/2024
        $this->updateSchema('Pelayanan Pasar', [
            ['key' => 'nama_pasar', 'label' => 'Nama Pasar', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Pasar Wameo', 'value' => 'Pasar Wameo'],
                ['label' => 'Pasar Karya Nugraha', 'value' => 'Pasar Karya Nugraha'],
                ['label' => 'Pasar Laelangi', 'value' => 'Pasar Laelangi'],
                ['label' => 'Pasar Moko', 'value' => 'Pasar Moko'],
                ['label' => 'Pasar Baruga', 'value' => 'Pasar Baruga'],
                ['label' => 'Pasar Modern Ngkari-ngkari', 'value' => 'Pasar Modern Ngkari-ngkari'],
            ]],
            ['key' => 'jenis_fasilitas', 'label' => 'Jenis Fasilitas', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Kios', 'value' => 'kios'],
                ['label' => 'Los', 'value' => 'los'],
                ['label' => 'Pelataran', 'value' => 'pelataran'],
                ['label' => 'Gudang', 'value' => 'gudang'],
                ['label' => 'Toilet / Sarana Penunjang', 'value' => 'toilet'],
            ]],
            ['key' => 'luas', 'label' => 'Luas Lapak / Kios (m2)', 'type' => 'number', 'required' => true],
        ], [
            ['key' => 'foto_lokasi', 'name' => 'Foto Kios / Los / Lapak', 'required' => true],
        ]);

        // 13. Persetujuan Bangunan Gedung (PBG) - 19 Parameter SIMBG
        $this->updateSchema('Bangunan Gedung', [
            ['key' => 'nama_bangunan', 'label' => 'Nama Bangunan / Proyek', 'type' => 'text', 'required' => true],
            ['key' => 'nomor_registrasi_simbg', 'label' => 'Nomor Registrasi SIMBG (Jika Ada)', 'type' => 'text'],
            ['key' => 'fungsi_bangunan', 'label' => 'Fungsi Bangunan Gedung', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Fungsi Hunian (Rumah Tinggal)', 'value' => 'hunian'],
                ['label' => 'Fungsi Keagamaan (Masjid/Gereja)', 'value' => 'keagamaan'],
                ['label' => 'Fungsi Usaha (Ruko/Toko/Hotel/Kantor)', 'value' => 'usaha'],
                ['label' => 'Fungsi Sosial dan Budaya (Sekolah/Klinik)', 'value' => 'sosial_budaya'],
                ['label' => 'Fungsi Khusus', 'value' => 'khusus'],
                ['label' => 'Fungsi Ganda / Campuran', 'value' => 'ganda'],
            ]],
            ['key' => 'permanensi', 'label' => 'Klasifikasi Permanensi', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Permanen', 'value' => 'permanen'],
                ['label' => 'Non-Permanen', 'value' => 'non_permanen'],
            ]],
            ['key' => 'tipe_konsultasi', 'label' => 'Tipe Konsultasi', 'type' => 'select', 'required' => true, 'options' => [
                ['label' => 'Bangunan Gedung Baru', 'value' => 'baru'],
                ['label' => 'Bangunan Gedung Eksisting / Renovasi', 'value' => 'renovasi'],
                ['label' => 'Bangunan Cagar Budaya', 'value' => 'cagar_budaya'],
            ]],
            ['key' => 'jumlah_unit', 'label' => 'Jumlah Unit Bangunan', 'type' => 'number', 'required' => true, 'default_value' => 1],
            ['key' => 'luas_bangunan', 'label' => 'Luas Total Bangunan (m2)', 'type' => 'number', 'required' => true],
            ['key' => 'jumlah_lantai', 'label' => 'Jumlah Lantai', 'type' => 'number', 'required' => true, 'default_value' => 1],
            ['key' => 'tinggi_bangunan', 'label' => 'Tinggi Bangunan (Meter)', 'type' => 'number'],
        ], [
            ['key' => 'dokumen_tanah', 'name' => 'Sertifikat Tanah (SHM/HGB/AJB)', 'required' => true],
            ['key' => 'dokumen_rencana_teknis', 'name' => 'Gambar Rencana Teknis (DED / Arsitektur)', 'required' => true],
            ['key' => 'ktp_pemilik', 'name' => 'KTP Pemilik / Pemohon', 'required' => true],
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

