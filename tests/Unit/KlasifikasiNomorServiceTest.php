<?php

namespace Tests\Unit;

use App\Services\KlasifikasiNomorService;
use Tests\TestCase;

class KlasifikasiNomorServiceTest extends TestCase
{
    private KlasifikasiNomorService $k;

    protected function setUp(): void
    {
        parent::setUp();
        $this->k = new KlasifikasiNomorService;
    }

    public function test_nomor_nop_patda_13_digit_dianggap_sudah_ada(): void
    {
        $this->assertTrue($this->k->sudahPunyaNomor('7017100030001'));
        $this->assertSame(KlasifikasiNomorService::ADA, $this->k->klasifikasi('7017100030001'));
    }

    public function test_nomor_bertitik_9pajak_dianggap_sudah_ada(): void
    {
        $this->assertTrue($this->k->sudahPunyaNomor('8.01.720.007.10000'));
        $this->assertTrue($this->k->sudahPunyaNomor('8.01.710.003.0189'));
        $this->assertTrue($this->k->sudahPunyaNomor('74.72.03.01.10.0001'));
    }

    public function test_nop_pbb_18_digit_dianggap_sudah_ada(): void
    {
        $this->assertTrue($this->k->sudahPunyaNomor('747101000123400001'));
    }

    public function test_nopd_baku_14_digit_dianggap_sudah_ada(): void
    {
        $this->assertTrue($this->k->sudahPunyaNomor('74720171010001'));
        $this->assertSame('74720171010001', $this->k->nomorBaku14('74720171010001'));
    }

    public function test_hash_32_hex_dianggap_placeholder_perlu_generate(): void
    {
        $this->assertSame(KlasifikasiNomorService::PLACEHOLDER, $this->k->klasifikasi('00014bebb952fca411cf74b64e5fef07'));
        $this->assertTrue($this->k->perluDigenerate('00014bebb952fca411cf74b64e5fef07'));
    }

    public function test_kosong_dan_null_perlu_generate(): void
    {
        $this->assertSame(KlasifikasiNomorService::KOSONG, $this->k->klasifikasi(null));
        $this->assertSame(KlasifikasiNomorService::KOSONG, $this->k->klasifikasi('   '));
        $this->assertTrue($this->k->perluDigenerate(null));
    }

    public function test_bentuk_tidak_standar_perlu_generate(): void
    {
        $this->assertSame(KlasifikasiNomorService::TIDAK_STANDAR, $this->k->klasifikasi('284885999-16-189'));
        $this->assertTrue($this->k->perluDigenerate('284885999-16-189'));
    }

    public function test_nomor_baku_hanya_untuk_prefix_7472(): void
    {
        // 7471 = prefix kota lain -> bukan nomor baku M-PAD Baubau
        $this->assertNull($this->k->nomorBaku14('74710100175400'));
        $this->assertNull($this->k->nomorBaku14('7017100030001'));
        $this->assertSame('74722271010007', $this->k->nomorBaku14('7472.22.7101.0007'));
    }

    public function test_bersihkan_membuang_spasi(): void
    {
        $this->assertSame('7017100030001', $this->k->bersihkan('  7017100030001  '));
        $this->assertNull($this->k->bersihkan('   '));
        $this->assertNull($this->k->bersihkan(null));
    }
}
