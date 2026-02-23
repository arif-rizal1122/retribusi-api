<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PbbBapendaService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $outlet;

    public function __construct()
    {
        $this->baseUrl  = config('services.pbb_bapenda.base_url', 'http://103.182.72.241:8000/pospbb/Api_pos');
        $this->username = config('services.pbb_bapenda.username', '');
        $this->password = config('services.pbb_bapenda.password', '');
        $this->outlet   = config('services.pbb_bapenda.outlet', 'ptpos');
    }

    /**
     * Mendapatkan token Bearer dari API Bapenda.
     * Token di-cache selama 23 jam (API berlaku 24 jam).
     */
    public function getToken(): string
    {
        return Cache::remember('pbb_bapenda_token', 23 * 60 * 60, function () {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/login", [
                    'USERNAME' => $this->username,
                    'PASSWORD' => $this->password,
                ]);

            $data = $response->json();

            if ($response->failed() || ($data['status'] ?? 0) !== 200) {
                Log::error('PBB Bapenda Login Failed', [
                    'status' => $data['status'] ?? 'unknown',
                    'msg'    => $data['msg'] ?? 'No message',
                ]);
                throw new \Exception('Gagal login ke server Bapenda: ' . ($data['msg'] ?? 'Unknown error'));
            }

            return $data['token'];
        });
    }

    /**
     * Hapus token cache (jika expired prematur).
     */
    public function clearToken(): void
    {
        Cache::forget('pbb_bapenda_token');
    }

    /**
     * Cek tagihan PBB berdasarkan NOP dan Tahun.
     */
    public function inquiry(string $nop, string $tahun): array
    {
        return $this->authenticatedRequest('inquiry', [
            'NOP'   => $nop,
            'TAHUN' => $tahun,
        ]);
    }

    /**
     * Melakukan pembayaran PBB.
     */
    public function payment(string $nop, string $tahun): array
    {
        return $this->authenticatedRequest('payment', [
            'NOP'   => $nop,
            'TAHUN' => $tahun,
        ]);
    }

    /**
     * Membatalkan pembayaran PBB (reversal).
     */
    public function reversal(string $nop, string $tahun, string $keterangan): array
    {
        return $this->authenticatedRequest('reversal', [
            'NOP'        => $nop,
            'TAHUN'      => $tahun,
            'KETERANGAN' => $keterangan,
        ]);
    }

    /**
     * Melakukan request yang membutuhkan autentikasi.
     * Otomatis retry 1x jika token expired.
     */
    protected function authenticatedRequest(string $endpoint, array $data): array
    {
        $attempts = 0;
        $maxAttempts = 2;

        while ($attempts < $maxAttempts) {
            $attempts++;

            try {
                $token = $this->getToken();

                $response = Http::timeout(30)
                    ->withToken($token)
                    ->post("{$this->baseUrl}/{$endpoint}", $data);

                $result = $response->json();

                // Jika token expired (401/403), clear cache dan retry
                if (in_array($response->status(), [401, 403]) || ($result['status'] ?? 0) === 401) {
                    $this->clearToken();
                    if ($attempts < $maxAttempts) {
                        continue;
                    }
                }

                Log::info("PBB Bapenda {$endpoint}", [
                    'request'  => $data,
                    'response' => $result,
                    'attempt'  => $attempts,
                ]);

                return $result;

            } catch (\Exception $e) {
                if ($attempts >= $maxAttempts) {
                    Log::error("PBB Bapenda {$endpoint} Error", [
                        'request' => $data,
                        'error'   => $e->getMessage(),
                    ]);
                    throw $e;
                }
                $this->clearToken();
            }
        }

        throw new \Exception('Gagal terhubung ke server Bapenda setelah beberapa percobaan.');
    }
}
