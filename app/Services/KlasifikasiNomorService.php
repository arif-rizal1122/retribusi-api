<?php

namespace App\Services;

/**
 * Klasifikasi nomor objek yang sudah ada vs yang perlu digenerate.
 *
 * Aturan M-PAD (arahan pengelola): objek yang SUDAH punya nomor cukup dipasang
 * (attach) tanpa diubah. Objek yang belum punya nomor baru digenerate NOPD/NORD.
 *
 * Bentuk yang dianggap SUDAH punya nomor:
 *   - 13 digit polos        : 7017100030001           (NOP PATDA lama)
 *   - 14 digit polos        : 74720171010001          (NOPD/NORD baku baru)
 *   - 18 digit polos        : 747101000123400001      (NOP PBB-P2)
 *   - bertitik 9pajak/SW_PATDA : 8.01.720.007.10000, 74.72.03.01.10.0001
 *
 * Bentuk yang dianggap BELUM punya nomor (perlu generate):
 *   - NULL / kosong
 *   - hash 32 hex (placeholder lama): 00014bebb952fca411cf74b64e5fef07
 *   - bentuk tidak standar (mis. 284885999-16-189)
 */
class KlasifikasiNomorService
{
    public const ADA = 'existing';

    public const PLACEHOLDER = 'placeholder';

    public const KOSONG = 'kosong';

    public const TIDAK_STANDAR = 'tidak_standar';

    public function klasifikasi(?string $nomor): string
    {
        $bersih = trim((string) $nomor);

        if ($bersih === '') {
            return self::KOSONG;
        }

        // Hash 32 hex = placeholder lama, bukan nomor asli
        if (preg_match('/^[0-9a-f]{32}$/i', $bersih)) {
            return self::PLACEHOLDER;
        }

        // Digit polos 13 / 14 / 18 (termasuk NOP PBB 18 digit dan NOP PATDA 13 digit)
        if (preg_match('/^[0-9]{13}$|^[0-9]{14}$|^[0-9]{18}$/', $bersih)) {
            return self::ADA;
        }

        // Format bertitik legacy: minimal 3 segmen angka bertitik
        if (preg_match('/^[0-9]+(\.[0-9]+){2,}$/', $bersih)) {
            return self::ADA;
        }

        return self::TIDAK_STANDAR;
    }

    public function sudahPunyaNomor(?string $nomor): bool
    {
        return $this->klasifikasi($nomor) === self::ADA;
    }

    public function perluDigenerate(?string $nomor): bool
    {
        return ! $this->sudahPunyaNomor($nomor);
    }

    /**
     * Nomor bentuk baku NOPD/NORD 14 digit (prefix 7472) bila cocok.
     */
    public function nomorBaku14(?string $nomor): ?string
    {
        $bersih = preg_replace('/[^0-9]/', '', (string) $nomor) ?? '';

        if (strlen($bersih) === 14 && str_starts_with($bersih, '7472')) {
            return $bersih;
        }

        return null;
    }

    /**
     * Bersihkan nomor: buang spasi, samakan huruf kecil hash ke huruf kecil.
     */
    public function bersihkan(?string $nomor): ?string
    {
        $bersih = trim((string) $nomor);

        return $bersih === '' ? null : $bersih;
    }
}
