<?php

namespace Tests\Unit;

use App\Services\LegacyDictionaryService;
use App\Services\ObjectNumberService;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Uji engine kodifikasi NOPD/NORD 14 digit dan normalisasi kunci kamus legacy.
 * Tidak menyentuh DB.
 */
class ObjectNumberServiceTest extends TestCase
{
    private ObjectNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ObjectNumberService;
    }

    public function test_format_nomor_selalu_14_digit(): void
    {
        $nomor = $this->service->generate('01', '7101', 1);

        $this->assertSame('7472017101', substr($nomor, 0, 10));
        $this->assertSame(14, strlen($nomor));
        $this->assertSame('74720171010001', $nomor);
    }

    public function test_nomor_dipisah_titik_untuk_tampilan(): void
    {
        $this->assertSame('7472.01.7101.0001', $this->service->formatTampilan('74720171010001'));
        $this->assertSame('7472.31.7899.9999', $this->service->formatTampilan('74723178999999'));
    }

    public function test_parse_mengembalikan_komponen_lengkap(): void
    {
        $hasil = $this->service->parse('74720671010042');

        $this->assertSame('7472', $hasil['kode_kota']);
        $this->assertSame('06', $hasil['sektor']);
        $this->assertSame('Pajak Reklame', $hasil['sektor_nama']);
        $this->assertSame('NOPD', $hasil['jenis']);
        $this->assertSame('71', $hasil['kode_kecamatan']);
        $this->assertSame('01', $hasil['kode_kelurahan']);
        $this->assertSame(42, $hasil['urut']);
        $this->assertSame('7472.06.7101.0042', $hasil['tampilan']);
    }

    public function test_sektor_retribusi_terdeteksi_nord(): void
    {
        $hasil = $this->service->parse('74722271010007');

        $this->assertSame('NORD', $hasil['jenis']);
        $this->assertSame('Retribusi Pelayanan Pasar', $hasil['sektor_nama']);
        $this->assertTrue($this->service->isSektorRetribusi('22'));
        $this->assertFalse($this->service->isSektorPajak('22'));
    }

    public function test_sektor_pajak_terdeteksi_nopd(): void
    {
        $this->assertTrue($this->service->isSektorPajak('09'));
        $this->assertFalse($this->service->isSektorRetribusi('09'));
        $this->assertSame('NOPD', $this->service->jenis('09'));
    }

    public function test_kode_wilayah_2_digit_dianggap_tingkat_kecamatan(): void
    {
        $this->assertSame('74720171000001', $this->service->generate('01', '71', 1));
    }

    public function test_sektor_tidak_dikenal_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generate('99', '7101', 1);
    }

    public function test_sektor_cadangan_di_luar_matriks_ditolak(): void
    {
        // 10-19 tidak dipakai, 32+ tidak ada dalam matriks baku
        $this->expectException(InvalidArgumentException::class);
        $this->service->generate('15', '7101', 1);
    }

    public function test_urut_di_luar_rentang_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generate('01', '7101', 10000);
    }

    public function test_urut_nol_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generate('01', '7101', 0);
    }

    public function test_kecamatan_di_luar_71_sampai_78_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generate('01', '7901', 1);
    }

    public function test_kelurahan_di_atas_15_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->generate('01', '7116', 1);
    }

    public function test_is_valid_menolak_kode_kota_lain(): void
    {
        $this->assertFalse($this->service->isValid('74730171010001'));
        $this->assertTrue($this->service->isValid('74720171010001'));
    }

    public function test_is_valid_menolak_panjang_salah(): void
    {
        $this->assertFalse($this->service->isValid('7472017101000'));
        $this->assertFalse($this->service->isValid('747201710100011'));
        $this->assertFalse($this->service->isValid(null));
        $this->assertFalse($this->service->isValid('7472AB71010001'));
    }

    public function test_normalisasi_input_membersihkan_titik_dan_spasi(): void
    {
        $this->assertSame('74720171010001', $this->service->normalisasiInput('7472.01.7101.0001'));
        $this->assertSame('74720171010001', $this->service->normalisasiInput(' 7472 01 7101 0001 '));
    }

    public function test_semua_sektor_matriks_bisa_generate(): void
    {
        foreach (array_keys($this->service->sektorMap()) as $sektor) {
            $nomor = $this->service->generate($sektor, '7101', 1);
            $this->assertTrue($this->service->isValid($nomor), "Sektor {$sektor} gagal generate");
        }
    }

    public function test_matriks_memuat_9_sektor_nopd_dan_12_sektor_nord(): void
    {
        $nopd = array_filter($this->service->sektorMap(), fn ($s) => $s['jenis'] === 'NOPD');
        $nord = array_filter($this->service->sektorMap(), fn ($s) => $s['jenis'] === 'NORD');

        $this->assertCount(9, $nopd);
        $this->assertCount(12, $nord);
    }

    public function test_kandidat_kode_menangani_nop_lama_9pajak(): void
    {
        $legacy = new LegacyDictionaryService($this->service);
        $kandidat = $legacy->kandidatKode('8.01.720.015.46944');

        $this->assertContains('8.01.720.015.46944', $kandidat);
        $this->assertContains('80172001546944', $kandidat);
    }

    public function test_kandidat_kode_menangani_npwpd_lama(): void
    {
        $legacy = new LegacyDictionaryService($this->service);
        $kandidat = $legacy->kandidatKode('P10000002087472');

        $this->assertContains('P10000002087472', $kandidat);
        $this->assertContains('10000002087472', $kandidat);
    }

    public function test_kandidat_kode_kosong_mengembalikan_array_kosong(): void
    {
        $legacy = new LegacyDictionaryService($this->service);

        $this->assertSame([], $legacy->kandidatKode('   '));
    }
}
