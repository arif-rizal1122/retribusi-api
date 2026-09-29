<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Penjaga Kualitas Kamus Referensi Legacy (read-only dictionary).
 *
 * Latar: ETL awal (29 Sep 2026) memasukkan 21 baris data uji pengembang
 * SW_PATDA (NOP "12345", "xcx", "adminpatda", brand "a"/"a1", subjek "WP Patda").
 * Baris seperti ini TIDAK boleh muncul sebagai kandidat klaim warga.
 *
 * Prinsip: TIDAK menghapus. Hanya menonaktifkan (is_active=0) + melaporkan.
 */
class KualitasKamus
{
    /** Pola subjek/entitas data uji pengembang SW_PATDA. */
    public const SUBJEK_UJI = [
        'WP Patda',
        'Asep Ardi Winardi',
        'AsepArdi',
        'Otong Sunandars',
        'Rijal Azani',
        'Winardi',
        'adminpatda',
    ];

    /**
     * Wilayah sah Kota Baubau = kode 7472 + 2 digit kecamatan (71-78) + 2 digit kelurahan.
     * Rentang terbukti dari data: 7101 s/d 7808 (43 prefix unik, semua Baubau).
     */
    public const KEC_MIN = 71;
    public const KEC_MAX = 78;

    /** Apakah 4 digit pertama kode wilayah termasuk wilayah Kota Baubau. */
    public static function wilayahBaubau(?string $kodeWilayah): bool
    {
        $w = preg_replace('/[^0-9]/', '', (string) $kodeWilayah);

        if (strlen($w) < 4) {
            return false;
        }

        $kec = (int) substr($w, 0, 2);
        $kel = (int) substr($w, 2, 2);

        return $kec >= self::KEC_MIN && $kec <= self::KEC_MAX && $kel >= 1;
    }

    /**
     * Alasan baris dianggap tidak layak (null = layak).
     */
    public static function alasanTidakLayak(object $baris): ?string
    {
        $kode = trim((string) ($baris->legacy_code ?? ''));
        $legal = trim((string) ($baris->legal_entity_name ?? ''));
        $brand = trim((string) ($baris->commercial_brand_name ?? ''));
        $wilayah = preg_replace('/[^0-9]/', '', (string) ($baris->kode_wilayah ?? ''));

        // 1. Tidak ada kode sama sekali
        if ($kode === '') {
            return 'kode_hilang';
        }

        // 2. Kode tidak dimulai angka, atau angka < 3 digit (mis. "a1", "6", "xcx")
        if (! preg_match('/^[0-9]/', $kode) || strlen(preg_replace('/[^0-9]/', '', $kode)) < 3) {
            return 'kode_tidak_valid';
        }

        // 3. Subjek = akun uji pengembang
        if (in_array($legal, self::SUBJEK_UJI, true)) {
            return 'subjek_uji';
        }

        // 4. Brand + legal sama-sama sampah (huruf tunggal / "a"/"a1")
        if ($brand !== '' && $legal !== '' && mb_strlen($brand) <= 2
            && mb_strlen($legal) <= 2 && strtolower($brand) === strtolower($legal)) {
            return 'nama_sampah';
        }

        // 5. Wilayah terisi tapi BUKAN kode Baubau (mis. 5303 = Kupang)
        if ($wilayah !== '' && ! self::wilayahBaubau($wilayah)) {
            return 'wilayah_luar_baubau';
        }

        // 6. Wilayah kosong -> tidak bisa diterbitkan nomor (klaim butuh 4 digit)
        if ($wilayah === '') {
            return 'wilayah_kosong';
        }

        return null;
    }

    /**
     * Pindai seluruh kamus, kembalikan ringkasan + daftar temuan.
     *
     * @return array{total:int,layak:int,tidak_layak:int,rincian:array<string,int>,temuan:array<int,array>}
     */
    public static function pindai(Collection $semua): array
    {
        $rincian = [];
        $temuan = [];
        $layak = 0;

        foreach ($semua as $baris) {
            $alasan = self::alasanTidakLayak($baris);

            if ($alasan === null) {
                $layak++;
                continue;
            }

            $rincian[$alasan] = ($rincian[$alasan] ?? 0) + 1;
            $temuan[] = [
                'id' => $baris->id ?? null,
                'legacy_code' => $baris->legacy_code ?? null,
                'legal_entity_name' => $baris->legal_entity_name ?? null,
                'sector_code' => $baris->sector_code ?? null,
                'kode_wilayah' => $baris->kode_wilayah ?? null,
                'alasan' => $alasan,
            ];
        }

        return [
            'total' => $semua->count(),
            'layak' => $layak,
            'tidak_layak' => count($temuan),
            'rincian' => $rincian,
            'temuan' => $temuan,
        ];
    }
}
