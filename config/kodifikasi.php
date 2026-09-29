<?php

/**
 * Kodifikasi objek pajak daerah (NOPD) dan retribusi daerah (NORD)
 * Kota Baubau — struktur 14 digit digital-ready.
 *
 * Format LAMA (existing, DIPERTAHANKAN APA ADANYA):
 *   7472 . XX (sektor) . XXXX (kec+kel) . XXXX (nomor urut titik)   = 14 digit
 *
 * Format BARU (generated, kapasitas diperluas):
 *   7472 . XX (sektor) . XXXX (kec+kel) . UUUUUU (nomor urut titik) = 16 digit
 *
 * Perluasan 29 Sep 2026: ember sektor 01 + kelurahan 7215 (Lipuu) memuat
 * 62.568 objek restoran > kapasitas 4 digit (9.999). Slot urut diperpanjang
 * ke 6 digit agar tidak ada objek gagal terbit. Nomor existing TIDAK diubah.
 *
 * Lingkup: Pajak daerah NON-PBB/BPHTB (NOPD) dan Retribusi Daerah (NORD).
 */

return [

    // Kode kota Baubau (BPS 7472)
    'kode_kota' => '7472',

    // Panjang total nomor (default, kompatibilitas existing)
    'panjang' => 14,

    // Panjang nomor BARU yang digenerate (slot urut 6 digit)
    'panjang_baru' => 16,

    // Panjang slot nomor urut (digit)
    'urut_panjang_lama' => 4,
    'urut_panjang_baru' => 6,

    // Rentang nomor urut titik
    'urut_min' => 1,
    'urut_max' => 9999,          // batas format lama (4 digit)
    'urut_max_baru' => 999999,   // batas format baru (6 digit)

    // ---- Alokasi nomor urut ACAK (sejak 29 Sep 2026) ----
    // Nomor urut tidak lagi berurutan (MAX+1) supaya urutan pendaftaran tidak
    // bocor lewat nomor. Rentang acak adaptif: makin padat ember makin lebar.
    'acak_faktor_ruang' => 8,     // langit = jumlah_terpakai * faktor
    'acak_margin' => 32,          // langit = urut_tertinggi + margin
    'acak_langit_minimum' => 64,  // langit minimum untuk ember baru/kosong
    'acak_percobaan_maks' => 50,  // batas ulang saat tabrakan unique

    // Kode kecamatan yang sah (2 digit) — 71 s/d 78
    'kode_kecamatan_min' => 71,
    'kode_kecamatan_max' => 78,

    // Sentinel kode kelurahan bila baru terpetakan sampai tingkat kecamatan
    'kode_kelurahan_sentinel' => '00',
    'kode_kelurahan_max' => 15,

    // Sektor NOPD (pajak daerah non-PBB/BPHTB) 01-09 dan NORD (retribusi daerah) 20-31
    'sektor' => [
        // ---- A. NOPD: Pajak Daerah Non-PBB/BPHTB ----
        '01' => ['nama' => 'PBJT Makanan dan/atau Minuman', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '02' => ['nama' => 'PBJT Jasa Perhotelan', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '03' => ['nama' => 'PBJT Jasa Kesenian dan Hiburan', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '04' => ['nama' => 'PBJT Jasa Parkir', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '05' => ['nama' => 'PBJT Tenaga Listrik', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '06' => ['nama' => 'Pajak Reklame', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '07' => ['nama' => 'Pajak Sarang Burung Walet', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '08' => ['nama' => 'Pajak Air Tanah (PAT)', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        '09' => ['nama' => 'Pajak Mineral Bukan Logam dan Batuan (MBLB)', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],

        // ---- B. NORD: Retribusi Daerah ----
        '20' => ['nama' => 'Retribusi Pelayanan Kebersihan / Persampahan', 'jenis' => 'NORD', 'opd' => 'DLH'],
        '21' => ['nama' => 'Retribusi Pelayanan Parkir di Tepi Jalan Umum', 'jenis' => 'NORD', 'opd' => 'Dishub'],
        '22' => ['nama' => 'Retribusi Pelayanan Pasar', 'jenis' => 'NORD', 'opd' => 'Disperindag'],
        '23' => ['nama' => 'Retribusi Pelayanan Kesehatan', 'jenis' => 'NORD', 'opd' => 'Dinkes / Labkesda'],
        '24' => ['nama' => 'Retribusi Tempat Pelelangan Ikan (TPI)', 'jenis' => 'NORD', 'opd' => 'Dinas Perikanan'],
        '25' => ['nama' => 'Retribusi Pelayanan Kepelabuhanan / Dermaga', 'jenis' => 'NORD', 'opd' => 'Dishub'],
        '26' => ['nama' => 'Retribusi Tempat Rekreasi, Pariwisata, dan Olahraga', 'jenis' => 'NORD', 'opd' => 'Dispar / Dispora'],
        '27' => ['nama' => 'Retribusi Pemanfaatan BMD / Sewa Aset Tanah dan Bangunan', 'jenis' => 'NORD', 'opd' => 'BKAD / Bapenda'],
        '28' => ['nama' => 'Retribusi Pemakaian Alat Berat dan Lab Konstruksi', 'jenis' => 'NORD', 'opd' => 'Dinas PUPR'],
        '29' => ['nama' => 'Retribusi Penyedotan Kakus / Tinja', 'jenis' => 'NORD', 'opd' => 'PUPR / DLH'],
        '30' => ['nama' => 'Retribusi Persetujuan Bangunan Gedung (PBG)', 'jenis' => 'NORD', 'opd' => 'PUPR / DPMPTSP'],
        '31' => ['nama' => 'Retribusi Penggunaan Tenaga Kerja Asing (PTKA)', 'jenis' => 'NORD', 'opd' => 'Disnaker / DPMPTSP'],
    ],

    // ---- Pajak MBLB (sektor 09) ----
    // Rumus Perda: (volume x harga_patokan) x tarif
    'mblb' => [
        'tarif' => 0.15,               // 15% sesuai Perda
        'ambang_selisih' => 0.10,      // toleransi selisih laporan vs ritase (10%)
        'terbit_walau_selisih' => false, // true = tetap terbit walau perlu diperiksa
    ],

    // ---- Identitas fisik ber-QR (retribusi daerah / NORD) ----
    // Stiker QR ditempel pada objek fisik: kios pasar, pos parkir, persil
    // sampah, lapak TPI. Token acak (bukan nomor NORD) dipakai di URL.
    'qr' => [
        'basis_url' => env('MPAD_QR_BASIS_URL', 'https://mpad.baubaukota.go.id'),
        'ukuran_default' => 320,
    ],

    // Jenis penomoran
    'jenis' => [
        'NOPD' => 'Nomor Objek Pajak Daerah',
        'NORD' => 'Nomor Objek Retribusi Daerah',
    ],
];