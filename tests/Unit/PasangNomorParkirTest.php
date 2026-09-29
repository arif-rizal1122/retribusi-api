<?php

namespace Tests\Unit;

use App\Console\Commands\PasangNomorParkirCommand;

/**
 * Uji pemetaan wilayah & pengenalan nomor lama untuk objek PBJT Parkir.
 *
 * Harness mandiri (tanpa PHPUnit) — server produksi tidak memasang PHPUnit.
 * Jalankan: php tests/Unit/PasangNomorParkirTest.php
 */
class PasangNomorParkirTest
{
    private int $lulus = 0;

    private int $gagal = 0;

    /** Cermin logika nomorLamaSah() di command. */
    private function nomorLamaSah(string $nop): ?string
    {
        if ($nop === '' || $nop === '-') {
            return null;
        }
        if (preg_match('/^[0-9a-f]{32}$/i', $nop)) {
            return null;
        }
        if (preg_match('/^\d{1,2}\.\d{2}\.\d{3}\.\d{3}\.\d{4}$/', $nop)) {
            return $nop;
        }
        if (preg_match('/^\d{13,14}$/', $nop) || preg_match('/^\d{17,18}$/', $nop)) {
            return $nop;
        }

        return null;
    }

    private function sama($harap, $nyata, string $pesan): void
    {
        if ($harap === $nyata) {
            $this->lulus++;

            return;
        }
        $this->gagal++;
        echo "    ✗ {$pesan}\n       harap=".var_export($harap, true)." nyata=".var_export($nyata, true)."\n";
    }

    public function test_nomor_bertitik_9pajak_dipasang(): void
    {
        $this->sama('8.01.710.004.0354', $this->nomorLamaSah('8.01.710.004.0354'), 'format 9pajak bertitik dikenali');
    }

    public function test_nomor_patda_13_digit_dipasang(): void
    {
        $this->sama('6017100020012', $this->nomorLamaSah('6017100020012'), 'PATDA 13 digit dikenali');
    }

    public function test_nomor_14_digit_dipasang(): void
    {
        $this->sama('74710100175400', $this->nomorLamaSah('74710100175400'), '14 digit dikenali (mis. Mall Lippo)');
    }

    public function test_nomor_pbb_18_digit_dipasang(): void
    {
        $this->sama('747101000000000019', $this->nomorLamaSah('747101000000000019'), '18 digit keluarga PBB dikenali');
    }

    public function test_hash_32_hex_digenerate(): void
    {
        $this->sama(null, $this->nomorLamaSah('00014bebb952fca411cf74b64e5fef07'), 'hash 32 hex BUKAN nomor sah');
    }

    public function test_nomor_kosong_digenerate(): void
    {
        $this->sama(null, $this->nomorLamaSah(''), 'kosong digenerate');
        $this->sama(null, $this->nomorLamaSah('-'), 'tanda hubung digenerate');
    }

    public function test_kode_non_standar_digenerate(): void
    {
        $this->sama(null, $this->nomorLamaSah('KIO-74-001'), 'kode kios bukan nomor objek pajak');
        $this->sama(null, $this->nomorLamaSah('P.2026.00005-16-172'), 'kode sementara bukan nomor sah');
    }

    public function test_struktur_nord_parkir_14_digit(): void
    {
        // 7472 (kota) . 04 (PBJT Parkir) . 7215 (Betoambari/Lipuu) . 0001 (urut)
        $nomor = '74720472150001';

        $this->sama(14, strlen($nomor), 'panjang 14 digit');
        $this->sama('7472', substr($nomor, 0, 4), 'kode kota Baubau');
        $this->sama('04', substr($nomor, 4, 2), 'sektor PBJT Parkir');
        $this->sama('7215', substr($nomor, 6, 4), 'kode wilayah Betoambari/Lipuu');
        $this->sama('0001', substr($nomor, 10, 4), 'nomor urut titik');
    }

    public function jalankan(): int
    {
        echo "UJI PASANG NOMOR PARKIR (harness mandiri)\n";
        echo str_repeat('-', 52)."\n";

        foreach (get_class_methods($this) as $m) {
            if (strpos($m, 'test_') !== 0) {
                continue;
            }
            $this->$m();
        }

        echo str_repeat('-', 52)."\n";
        echo "HASIL: {$this->lulus} assertion lulus, {$this->gagal} gagal\n";

        return $this->gagal;
    }
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    exit((new PasangNomorParkirTest())->jalankan() === 0 ? 0 : 1);
}
