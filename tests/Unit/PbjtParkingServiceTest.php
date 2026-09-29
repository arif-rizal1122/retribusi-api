<?php

namespace Tests\Unit;

use App\Services\PbjtParkingService;

/**
 * Uji kalkulator PBJT Parkir — harness mandiri (tanpa PHPUnit).
 *
 * Server produksi tidak memasang PHPUnit, jadi kelas ini memakai assertion
 * sederhana bawaan agar tetap bisa dijalankan dan dibuktikan di prod
 * lewat: php tests/Unit/PbjtParkingServiceTest.php
 *
 * Angka acuan dari data NYATA produksi:
 * `monthly_reports` id=3 → omzet 545.699.500, pajak 54.569.950 (tepat 10%).
 */
class PbjtParkingServiceTest
{
    private PbjtParkingService $svc;

    private int $lulus = 0;

    private int $gagal = 0;

    public function __construct()
    {
        $this->svc = new PbjtParkingService();
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

    private function benar(bool $syarat, string $pesan): void
    {
        $this->sama(true, $syarat, $pesan);
    }

    public function test_hitung_tarif_10_persen_sesuai_data_produksi(): void
    {
        $h = $this->svc->hitung(545_699_500.0, 0.0, 0);

        $this->sama(545_699_500.0, $h['dpp_amount'], 'dpp = omzet kotor');
        $this->sama(54_569_950.0, $h['principal_tax'], 'pokok = 10% dari 545.699.500');
        $this->sama(0.0, $h['penalty_amount'], 'tanpa denda');
        $this->sama(54_569_950.0, $h['total_bill'], 'total = pokok');
        $this->sama(10.0, $h['tax_rate'], 'tarif tersimpan 10%');
    }

    public function test_omzet_ditambah_pendapatan_lain(): void
    {
        $h = $this->svc->hitung(10_000_000.0, 2_000_000.0, 0);

        $this->sama(12_000_000.0, $h['dpp_amount'], 'dpp = omzet + pendapatan lain');
        $this->sama(1_200_000.0, $h['principal_tax'], 'pokok = 10% dari dpp');
    }

    public function test_denda_1_persen_per_bulan_dari_pokok(): void
    {
        $h = $this->svc->hitung(10_000_000.0, 2_000_000.0, 3);

        $this->sama(1_200_000.0, $h['principal_tax'], 'pokok');
        $this->sama(3, $h['penalty_months'], 'bulan denda');
        $this->sama(36_000.0, $h['penalty_amount'], 'denda 1% x 3 bulan = 36.000');
        $this->sama(1_236_000.0, $h['total_bill'], 'total = pokok + denda');
    }

    public function test_denda_dibatasi_maksimum_bulan(): void
    {
        $h = $this->svc->hitung(1_000_000.0, 0.0, 999);

        $this->sama(PbjtParkingService::DENDA_MAKS_BULAN, $h['penalty_months'], 'denda dibatasi maksimum');
    }

    public function test_denda_negatif_dinormalkan_nol(): void
    {
        $h = $this->svc->hitung(1_000_000.0, 0.0, -5);

        $this->sama(0, $h['penalty_months'], 'bulan denda nol');
        $this->sama(0.0, $h['penalty_amount'], 'denda nol');
    }

    public function test_tarif_kustom_dihormati(): void
    {
        $h = $this->svc->hitung(1_000_000.0, 0.0, 0, 5.0);

        $this->sama(5.0, $h['tax_rate'], 'tarif 5% dihormati');
        $this->sama(50_000.0, $h['principal_tax'], 'pokok 5%');
    }

    public function test_omzet_nol_tidak_error(): void
    {
        $h = $this->svc->hitung(0.0, 0.0, 0);

        $this->sama(0.0, $h['principal_tax'], 'pokok nol');
        $this->sama(0.0, $h['total_bill'], 'total nol');
    }

    public function test_anomali_terdeteksi_saat_omzet_jatuh_drastis(): void
    {
        $a = $this->svc->periksaAnomali(1_000_000.0, 545_699_500.0);

        $this->benar($a['anomali'], 'anomali terdeteksi');
        $this->benar($a['rasio'] < 1.0, 'rasio sangat rendah');
    }

    public function test_tidak_anomali_saat_omzet_wajar(): void
    {
        $a = $this->svc->periksaAnomali(480_000_000.0, 545_699_500.0);

        $this->benar(! $a['anomali'], 'tidak dianggap anomali');
        $this->benar($a['rasio'] > 40.0, 'rasio wajar');
    }

    public function test_tanpa_baseline_tidak_dianggap_anomali(): void
    {
        $a = $this->svc->periksaAnomali(1_000_000.0, 0.0);

        $this->benar(! $a['anomali'], 'tanpa baseline bukan anomali');
        $this->sama('tanpa baseline', $a['alasan'], 'alasan tepat');
    }

    public function test_susun_payload_memakai_format_period_bulan_tahun(): void
    {
        $h = $this->svc->hitung(1_000_000.0, 0.0, 0);
        $p = $this->svc->susunPayload([
            'taxpayer_id' => 77,
            'tax_object_id' => 76252,
            'tax_period_month' => 9,
            'tax_period_year' => 2026,
            'gross_turnover' => 1_000_000,
            'other_income' => 0,
        ], $h);

        $this->sama('9-2026', $p['period'], 'format period M-YYYY sesuai monthly_reports');
        $this->sama(100_000.0, $p['tax_amount'], 'pokok 10%');
        $this->sama('pending', $p['status'], 'status awal pending');
        $this->sama('manual', $p['source'], 'sumber manual');
    }

    /**
     * Jalankan semua test_* dan laporkan hasilnya.
     */
    public function jalankan(): int
    {
        echo "UJI PBJT PARKIR (harness mandiri)\n";
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

// Dijalankan langsung: php tests/Unit/PbjtParkingServiceTest.php
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    require_once __DIR__.'/../../vendor/autoload.php';
    exit((new PbjtParkingServiceTest())->jalankan() === 0 ? 0 : 1);
}
