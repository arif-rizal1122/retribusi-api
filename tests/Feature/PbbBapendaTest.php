<?php

namespace Tests\Feature;

use App\Models\Taxpayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PbbBapendaTest extends TestCase
{
    use RefreshDatabase;

    protected string $baseUrl = 'http://103.182.72.241:8000/pospbb/Api_pos';

    /**
     * Test successful inquiry.
     */
    public function test_pbb_inquiry_success()
    {
        Http::fake([
            "{$this->baseUrl}/login" => Http::response([
                'status' => 200,
                'token' => 'mocked-token',
                'msg' => 'Success'
            ], 200),
            "{$this->baseUrl}/inquiry" => Http::response([
                'status' => 200,
                'nop' => '123456789012345678',
                'tahun' => '2026',
                'nama_wp' => 'BUDI SANTOSO',
                'alamat_wp' => 'JL. MERDEKA NO 1',
                'kelurahan' => 'KAMPUNG BARU',
                'kota' => 'BAUBAU',
                'pbb_pokok' => 500000,
                'denda' => 0,
                'total_harus_dibayar' => 500000,
                'status_bayar' => 'BELUM BAYAR'
            ], 200),
        ]);

        $response = $this->postJson('/api/pbb/bapenda/inquiry', [
            'nop' => '123456789012345678',
            'tahun' => '2026'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'nama_wp' => 'BUDI SANTOSO',
                    'total_harus_dibayar' => 500000
                ]
            ]);
    }

    /**
     * Test inquiry when NOP is not found.
     */
    public function test_pbb_inquiry_not_found()
    {
        Http::fake([
            "{$this->baseUrl}/login" => Http::response([
                'status' => 200,
                'token' => 'mocked-token'
            ], 200),
            "{$this->baseUrl}/inquiry" => Http::response([
                'status' => 404,
                'msg' => 'Data tidak ditemukan'
            ], 200), // Bapenda API sometimes returns 200 with status 404 in body
        ]);

        $response = $this->postJson('/api/pbb/bapenda/inquiry', [
            'nop' => '000000000000000000',
            'tahun' => '2026'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Data tidak ditemukan'
            ]);
    }

    /**
     * Test inquiry when Bapenda login fails (triggers 500).
     */
    public function test_pbb_inquiry_login_failure()
    {
        Http::fake([
            "{$this->baseUrl}/login" => Http::response([
                'status' => 404,
                'msg' => 'No data found'
            ], 200),
        ]);

        $response = $this->postJson('/api/pbb/bapenda/inquiry', [
            'nop' => '123456789012345678',
            'tahun' => '2026'
        ]);

        // Based on controller logic, this should return 500 with custom message
        $response->assertStatus(500)
            ->assertJson([
                'status' => 'error',
                'message' => 'Gagal terhubung ke server Bapenda. Silakan coba lagi.'
            ]);
    }

    /**
     * Test validation error.
     */
    public function test_pbb_inquiry_validation_error()
    {
        $response = $this->postJson('/api/pbb/bapenda/inquiry', [
            'nop' => 'short',
            'tahun' => 'abc'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nop', 'tahun']);
    }
}
