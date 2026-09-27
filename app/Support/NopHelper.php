<?php

namespace App\Support;

/**
 * NopHelper — Normalisasi & validasi Nomor Objek Pajak (NOP).
 *
 * Task 2325 — Keputusan format NOP.
 *
 * KEPUTUSAN: format resmi 18 digit dipertahankan.
 *
 * Dasar:
 * - Standar nasional NOP PBB-P2 (Ditjen PBB / Perdirjen) adalah 18 digit:
 *     PP KKK Kec Kel Blok Urut C
 *     74 710 100 100 100  010   1
 *     |  |   |   |   |    |     +-- [17]    digit cek (1 digit)
 *     |  |   |   |   |    +-------- [14-16] nomor urut (3 digit)
 *     |  |   |   |   +------------- [11-13] kode blok (3 digit)
 *     |  |   |   +----------------- [8-10]  kode kelurahan (3 digit)
 *     |  |   +--------------------- [5-7]   kode kecamatan (3 digit)
 *     |  +------------------------- [2-4]   kode kabupaten/kota (3 digit)
 *     +---------------------------- [0-1]   kode provinsi (2 digit)
 * - Seluruh data produksi (sppt_records.nop) sudah 18 digit, konsisten.
 * - Format 14 digit yang disebut sebagian dokumen adalah NOP versi ringkas
 *   (tanpa kode provinsi & digit cek) — BUKAN standar nasional yang berlaku.
 *   Menerapkannya akan memutus kompatibilitas dengan data existing dan
 *   layanan H2H BPN/Bhumi yang memakai NOP 18 digit.
 *
 * Helper ini TIDAK mengubah data; hanya menormalkan input dan menolak
 * panjang yang tidak didukung dengan pesan yang jelas.
 */
class NopHelper
{
    /** Panjang NOP resmi. */
    public const LENGTH = 18;

    /** Panjang NOP versi ringkas lama (ditolak, tapi dikenali untuk pesan). */
    public const LENGTH_LEGACY_SHORT = 14;

    /**
     * Bersihkan input NOP: buang spasi, titik, dan tanda hubung.
     */
    public static function normalize(?string $nop): string
    {
        if ($nop === null) {
            return '';
        }

        return preg_replace('/[\s.\-]/', '', trim($nop)) ?? '';
    }

    /**
     * Apakah NOP valid (18 digit numerik).
     */
    public static function isValid(?string $nop): bool
    {
        $clean = self::normalize($nop);

        return strlen($clean) === self::LENGTH && ctype_digit($clean);
    }

    /**
     * Apakah input berupa NOP versi ringkas 14 digit.
     */
    public static function isLegacyShort(?string $nop): bool
    {
        $clean = self::normalize($nop);

        return strlen($clean) === self::LENGTH_LEGACY_SHORT && ctype_digit($clean);
    }

    /**
     * Pesan kesalahan yang menjelaskan, bukan sekadar "invalid".
     */
    public static function validationMessage(?string $nop): string
    {
        $clean = self::normalize($nop);
        $len = strlen($clean);

        if ($len === 0) {
            return 'NOP wajib diisi.';
        }

        if (! ctype_digit($clean)) {
            return 'NOP hanya boleh berisi angka (18 digit).';
        }

        if (self::isLegacyShort($clean)) {
            return 'NOP yang dikirim '.self::LENGTH_LEGACY_SHORT.' digit — itu format ringkas lama. '
                .'Sistem memakai NOP resmi '.self::LENGTH.' digit (PP.KKK.Kec.Kel.Blok.Urut.C). '
                .'Mohon lengkapi kode provinsi di depan dan digit cek di belakang.';
        }

        if ($len !== self::LENGTH) {
            return 'NOP harus '.self::LENGTH.' digit, yang dikirim '.$len.' digit.';
        }

        return 'NOP tidak valid.';
    }

    /**
     * Uraikan NOP menjadi komponen wilayah. Null bila tidak valid.
     *
     * @return array{province:string, city:string, district:string, village:string, block:string, serial:string, check_digit:string}|null
     */
    public static function parse(?string $nop): ?array
    {
        if (! self::isValid($nop)) {
            return null;
        }

        $n = self::normalize($nop);

        return [
            'province'    => substr($n, 0, 2),
            'city'        => substr($n, 2, 3),
            'district'    => substr($n, 5, 3),
            'village'     => substr($n, 8, 3),
            'block'       => substr($n, 11, 3),
            'serial'      => substr($n, 14, 3),
            'check_digit' => substr($n, 17, 1),
        ];
    }

    /**
     * Format NOP untuk tampilan: 74.710.100.100.100-010.1
     */
    public static function format(?string $nop): string
    {
        $p = self::parse($nop);

        if (! $p) {
            return (string) $nop;
        }

        return sprintf(
            '%s.%s.%s.%s.%s-%s.%s',
            $p['province'],
            $p['city'],
            $p['district'],
            $p['village'],
            $p['block'],
            $p['serial'],
            $p['check_digit']
        );
    }

    /**
     * Samarkan NOP untuk tampilan publik / peta umum:
     * Contoh: 747201000100100010 -> 74.72.***.***.***-001.0
     * Sesuai UU PDP No. 27/2022 untuk mencegah profiling aset tanpa otorisasi.
     */
    public static function mask(?string $nop): string
    {
        $clean = self::normalize($nop);
        if (strlen($clean) !== self::LENGTH) {
            return (string) $nop;
        }

        $p = self::parse($clean);
        if (! $p) {
            return (string) $nop;
        }

        return sprintf(
            '%s.%s.***.***.***-%s.%s',
            $p['province'],
            $p['city'],
            $p['serial'],
            $p['check_digit']
        );
    }

    /**
     * Samarkan nama wajib pajak untuk antarmuka publik:
     * Menampilkan inisial huruf pertama diikuti asteris (*) untuk setiap kata.
     * Contoh: "MUHDAN FYAN" -> "M***** F***"
     * Contoh: "BUDI SANTOSO" -> "B*** S******"
     */
    public static function maskName(?string $name): string
    {
        if (empty($name)) {
            return '-';
        }

        $trimmed = trim($name);
        $words = preg_split('/\s+/', $trimmed);
        $maskedWords = [];

        foreach ($words as $word) {
            $len = mb_strlen($word);
            if ($len <= 1) {
                $maskedWords[] = $word;
            } elseif ($len === 2) {
                $maskedWords[] = mb_substr($word, 0, 1).'*';
            } else {
                $maskedWords[] = mb_substr($word, 0, 1).str_repeat('*', min(5, $len - 1));
            }
        }

        return implode(' ', $maskedWords);
    }
}
