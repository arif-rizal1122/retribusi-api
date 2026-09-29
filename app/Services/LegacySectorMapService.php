<?php

namespace App\Services;

/**
 * Peta tabel legacy SW_PATDA -> sektor matriks baku M-PAD.
 *
 * Dipakai untuk memastikan sektor objek dari metadata.legacy_table (PASTI),
 * bukan menebak dari nama.
 */
class LegacySectorMapService
{
    /** @var array<string, array{sektor:string, nama:string, jenis:string, opd:string}> */
    public const PETA = [
        'PATDA_RESTORAN_PROFIL' => ['sektor' => '01', 'nama' => 'PBJT Makanan dan/atau Minuman', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_HOTEL_PROFIL' => ['sektor' => '02', 'nama' => 'PBJT Jasa Perhotelan', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_HIBURAN_PROFIL' => ['sektor' => '03', 'nama' => 'PBJT Jasa Kesenian dan Hiburan', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_PARKIR_PROFIL' => ['sektor' => '04', 'nama' => 'PBJT Jasa Parkir', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_LISTRIK_PROFIL' => ['sektor' => '05', 'nama' => 'PBJT Tenaga Listrik', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_REKLAME_PROFIL' => ['sektor' => '06', 'nama' => 'Pajak Reklame', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_WALET_PROFIL' => ['sektor' => '07', 'nama' => 'Pajak Sarang Burung Walet', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_AIRBAWAHTANAH_PROFIL' => ['sektor' => '08', 'nama' => 'Pajak Air Tanah (PAT)', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_MINERAL_PROFIL' => ['sektor' => '09', 'nama' => 'Pajak Mineral Bukan Logam dan Batuan (MBLB)', 'jenis' => 'NOPD', 'opd' => 'Bapenda'],
        'PATDA_JALAN_PROFIL' => ['sektor' => '21', 'nama' => 'Retribusi Pelayanan Parkir di Tepi Jalan Umum', 'jenis' => 'NORD', 'opd' => 'Dishub'],
    ];

    /**
     * Estimasi sektor dari tarif pajak, HANYA untuk data lama yang tidak punya legacy_table.
     * Ini pendekatan cadangan yang konservatif: kalau ambigu, kembalikan null (jangan menebak).
     */
    public const TARIF_KE_SEKTOR = [
        '25' => '06', // reklame 25%
        '40' => '09', // MBLB 40% (perlu konfirmasi)
    ];

    public function dariTabelLegacy(?string $tabel): ?array
    {
        $tabel = trim((string) $tabel);

        if ($tabel === '') {
            return null;
        }

        return self::PETA[$tabel] ?? null;
    }

    public function sektorDariTabelLegacy(?string $tabel): ?string
    {
        return $this->dariTabelLegacy($tabel)['sektor'] ?? null;
    }

    /**
     * Cadangan: sektor dari tarif. Ambigu -> null (tidak menebak).
     * Tarif 10% dipakai bersama makanan/minuman (01) dan perhotelan (02),
     * 20% dipakai bersama hiburan (03) dan parkir (04) -> keduanya dikembalikan sebagai kandidat.
     *
     * @return array<int, string>
     */
    public function kandidatSektorDariTarif(mixed $tarif): array
    {
        $tarif = trim((string) $tarif);

        return match ($tarif) {
            '25' => ['06'],
            '40' => ['09'],
            '15' => ['07'],
            '5' => ['08'],
            '10' => ['01', '02'],
            '20' => ['03', '04'],
            default => [],
        };
    }

    public function semuaTabel(): array
    {
        return array_keys(self::PETA);
    }
}
