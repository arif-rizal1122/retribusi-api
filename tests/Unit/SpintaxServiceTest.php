<?php

namespace Tests\Unit;

use App\Services\SpintaxService;
use PHPUnit\Framework\TestCase;

class SpintaxServiceTest extends TestCase
{
    public function test_spin_resolves_options(): void
    {
        $template = '{Halo|Hai|Selamat Pagi} Wajib Pajak';
        $result = SpintaxService::spin($template);

        $this->assertTrue(
            in_array($result, ['Halo Wajib Pajak', 'Hai Wajib Pajak', 'Selamat Pagi Wajib Pajak'], true)
        );
    }

    public function test_interpolate_replaces_curly_and_colon_placeholders(): void
    {
        $template = 'Yth. {nama}, tagihan Anda :nominal.';
        $result = SpintaxService::interpolate($template, [
            'nama' => 'Budi Santoso',
            'nominal' => 'Rp 50.000',
        ]);

        $this->assertSame('Yth. Budi Santoso, tagihan Anda Rp 50.000.', $result);
    }

    public function test_combined_spin_and_interpolate(): void
    {
        $template = '{Halo|Hai} {nama}, status: {status}';
        $interpolated = SpintaxService::interpolate($template, [
            'nama' => 'Ahmad',
            'status' => 'Lunas',
        ]);
        $spun = SpintaxService::spin($interpolated);

        $this->assertTrue(
            in_array($spun, ['Halo Ahmad, status: Lunas', 'Hai Ahmad, status: Lunas'], true)
        );
    }
}
