<?php

/**
 * Kodifikasi objek pajak daerah (NOPD) dan retribusi daerah (NORD)
 * Kota Baubau — struktur 14 digit digital-ready.
 *
 * Format: 7472 . XX (sektor) . XXXX (kode kecamatan + kelurahan) . XXXX (nomor urut titik)
 * Lingkup: Pajak daerah NON-PBB/BPHTB (NOPD) dan Retribusi Daerah (NORD).
 */

return [

    // Kode kota Baubau (BPS 7472)
    'kode_kota' => '7472',

    // Panjang total nomor
    'panjang' => 14,

    // Rentang nomor urut titik (4 digit)
    'urut_min' => 1,
    'urut_max' => 9999,

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

    // Jenis penomoran
    'jenis' => [
        'NOPD' => 'Nomor Objek Pajak Daerah',
        'NORD' => 'Nomor Objek Retribusi Daerah',
    ],
];
