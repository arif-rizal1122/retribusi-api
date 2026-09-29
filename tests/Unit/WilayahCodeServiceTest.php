<?php

namespace Tests\Unit;

use App\Services\WilayahCodeService;
use InvalidArgumentException;
use Tests\TestCase;

class WilayahCodeServiceTest extends TestCase
{
    private WilayahCodeService $wilayah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wilayah = new WilayahCodeService;
    }

    public function test_legacy_kecamatan_dipetakan_ke_kode_dua_digit(): void
    {
        $this->assertSame('71', $this->wilayah->dariLegacyKecamatan('7472710')); // Wolio
        $this->assertSame('72', $this->wilayah->dariLegacyKecamatan('7472720')); // Betoambari
        $this->assertSame('78', $this->wilayah->dariLegacyKecamatan('7472780')); // Lea-Lea
    }

    public function test_legacy_kecamatan_tidak_valid_ditolak(): void
    {
        $this->assertNull($this->wilayah->dariLegacyKecamatan('7472790'));
        $this->assertNull($this->wilayah->dariLegacyKecamatan('1234567'));
        $this->assertNull($this->wilayah->dariLegacyKecamatan(''));
        $this->assertNull($this->wilayah->dariLegacyKecamatan(null));
    }

    public function test_legacy_kelurahan_dipetakan_ke_kode_wilayah_empat_digit(): void
    {
        $this->assertSame([
            'kode_wilayah' => '7103',
            'kode_kecamatan' => '71',
            'kode_kelurahan' => '03',
        ], $this->wilayah->dariLegacyKelurahan('7472710003')); // Wale

        $this->assertSame('7215', $this->wilayah->dariLegacyKelurahan('7472720015')['kode_wilayah']); // Lipuu
        $this->assertSame('7101', $this->wilayah->dariLegacyKelurahan('7472710001')['kode_wilayah']); // Bataraguru
    }

    public function test_legacy_kelurahan_di_luar_rentang_ditolak(): void
    {
        $this->assertNull($this->wilayah->dariLegacyKelurahan('7472710016'));
        $this->assertNull($this->wilayah->dariLegacyKelurahan('7472710003x'));
    }

    public function test_bolak_balik_kode_wilayah_dan_kode_sw_patda(): void
    {
        foreach (['7101', '7103', '7215', '7702', '7807'] as $kodeWilayah) {
            $legacy = $this->wilayah->keLegacyKelurahan($kodeWilayah);
            $balik = $this->wilayah->dariLegacyKelurahan($legacy);

            $this->assertSame($kodeWilayah, $balik['kode_wilayah'], "Bolak-balik gagal untuk {$kodeWilayah}");
        }
    }

    public function test_kode_wilayah_dari_pasangan_kecamatan_kelurahan(): void
    {
        $this->assertSame('7103', $this->wilayah->kodeWilayah('71', '03'));
        $this->assertSame('7103', $this->wilayah->kodeWilayah('71', '3'));
    }

    public function test_kode_kecamatan_dan_kelurahan_tidak_valid_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->wilayah->kodeWilayah('70', '03');
    }

    public function test_ke_legacy_kecamatan(): void
    {
        $this->assertSame('7472710', $this->wilayah->keLegacyKecamatan('7103'));
        $this->assertSame('7472780', $this->wilayah->keLegacyKecamatan('7807'));
    }
}
