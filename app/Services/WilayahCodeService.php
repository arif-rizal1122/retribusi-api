<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Pemetaan kode wilayah SW_PATDA (data lama) <-> kode wilayah M-PAD 4 digit.
 *
 * SW_PATDA: kecamatan 7 digit (7472 + 7X + 0), kelurahan 10 digit (+ 3 digit nomor kelurahan)
 * M-PAD   : kecamatan 2 digit (71-78), kelurahan 2 digit (01-15), digabung jadi 4 digit
 *
 * Contoh: 7472710003 -> 71 + 03 -> 7103 (Kecamatan WOLIO, Kelurahan WALE)
 */
class WilayahCodeService
{
    public const PREFIX_KOTA = '7472';

    public function dariLegacyKecamatan(?string $legacyKode): ?string
    {
        $legacyKode = trim((string) $legacyKode);

        if (! preg_match('/^7472(\d{2})0$/', $legacyKode, $m)) {
            return null;
        }

        $kecamatan = $m[1];
        if ((int) $kecamatan < 71 || (int) $kecamatan > 78) {
            return null;
        }

        return $kecamatan;
    }

    /**
     * @return array{kode_wilayah:string,kode_kecamatan:string,kode_kelurahan:string}|null
     */
    public function dariLegacyKelurahan(?string $legacyKode): ?array
    {
        $legacyKode = trim((string) $legacyKode);

        if (! preg_match('/^7472(\d{2})0(\d{3})$/', $legacyKode, $m)) {
            return null;
        }

        $kecamatan = $m[1];
        if ((int) $kecamatan < 71 || (int) $kecamatan > 78) {
            return null;
        }

        $kelurahan = str_pad((string) (int) $m[2], 2, '0', STR_PAD_LEFT);
        if ((int) $kelurahan > 15) {
            return null;
        }

        return [
            'kode_wilayah' => $kecamatan.$kelurahan,
            'kode_kecamatan' => $kecamatan,
            'kode_kelurahan' => $kelurahan,
        ];
    }

    /**
     * Kode 4 digit dari pasangan kode kecamatan + kelurahan.
     */
    public function kodeWilayah(string $kodeKecamatan, string $kodeKelurahan): string
    {
        $kecamatan = str_pad($kodeKecamatan, 2, '0', STR_PAD_LEFT);
        $kelurahan = str_pad($kodeKelurahan, 2, '0', STR_PAD_LEFT);

        if ((int) $kecamatan < 71 || (int) $kecamatan > 78) {
            throw new InvalidArgumentException("Kode kecamatan '{$kecamatan}' di luar rentang 71-78.");
        }

        if ((int) $kelurahan > 15) {
            throw new InvalidArgumentException("Kode kelurahan '{$kelurahan}' di luar rentang 00-15.");
        }

        return $kecamatan.$kelurahan;
    }

    /**
     * Kebalikannya: 7103 -> 7472710003 (kode kelurahan SW_PATDA).
     */
    public function keLegacyKelurahan(string $kodeWilayah): string
    {
        $kodeWilayah = preg_replace('/[^0-9]/', '', $kodeWilayah) ?? '';

        if (strlen($kodeWilayah) !== 4) {
            throw new InvalidArgumentException('Kode wilayah harus 4 digit.');
        }

        $kecamatan = substr($kodeWilayah, 0, 2);
        $kelurahan = substr($kodeWilayah, 2, 2);

        if ((int) $kecamatan < 71 || (int) $kecamatan > 78) {
            throw new InvalidArgumentException("Kode kecamatan '{$kecamatan}' di luar rentang 71-78.");
        }

        return self::PREFIX_KOTA.$kecamatan.'0'.str_pad((string) (int) $kelurahan, 3, '0', STR_PAD_LEFT);
    }

    public function keLegacyKecamatan(string $kodeWilayah): string
    {
        $kodeWilayah = preg_replace('/[^0-9]/', '', $kodeWilayah) ?? '';

        if (strlen($kodeWilayah) < 2) {
            throw new InvalidArgumentException('Kode wilayah minimal 2 digit.');
        }

        return self::PREFIX_KOTA.substr($kodeWilayah, 0, 2).'0';
    }

    /**
     * Nama kecamatan + kelurahan dari ref_wilayah (master hasil seed).
     */
    public function nama(string $kodeWilayah): ?array
    {
        $row = DB::table('ref_wilayah')->where('kode', $kodeWilayah)->first();

        if (! $row) {
            return null;
        }

        return [
            'kode_wilayah' => $row->kode,
            'kecamatan' => $row->kecamatan,
            'kelurahan' => $row->kelurahan,
            'legacy_kode' => $row->legacy_kode ?? null,
        ];
    }

    /**
     * Cari kode wilayah dari nama kelurahan (untuk ETL / auto-complete alamat).
     */
    public function cariDariNama(?string $kelurahan, ?string $kecamatan = null): ?array
    {
        $kelurahan = trim((string) $kelurahan);
        if ($kelurahan === '') {
            return null;
        }

        $query = DB::table('ref_wilayah')->whereRaw('UPPER(kelurahan) = ?', [mb_strtoupper($kelurahan)]);

        if ($kecamatan !== null && trim($kecamatan) !== '') {
            $query->whereRaw('UPPER(kecamatan) = ?', [mb_strtoupper(trim($kecamatan))]);
        }

        $row = $query->first();

        return $row ? $this->nama($row->kode) : null;
    }

    /**
     * Daftar kecamatan dari master.
     *
     * @return array<int, array{kode_kecamatan:string,kecamatan:string}>
     */
    public function semuaKecamatan(): array
    {
        return DB::table('ref_wilayah')
            ->select('kode_kecamatan', 'kecamatan')
            ->distinct()
            ->orderBy('kode_kecamatan')
            ->get()
            ->map(fn ($r) => ['kode_kecamatan' => $r->kode_kecamatan, 'kecamatan' => $r->kecamatan])
            ->all();
    }
}
