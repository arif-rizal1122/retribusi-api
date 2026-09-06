<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DpmptspValidationService
{
    private bool $enabled;
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->enabled = (bool) config('services.bapenda.dpmptsp.enabled', false);
        $this->baseUrl = (string) config('services.bapenda.dpmptsp.base_url', '');
        $this->apiKey = (string) config('services.bapenda.dpmptsp.api_key', '');
    }

    /**
     * Validate IMB / PBG number for reklame or permanent structures.
     * Fail-open design: Returns verification result array without breaking flow if service is offline.
     *
     * @param  string|null  $imbPbgNumber
     * @param  string|null  $nib
     * @return array{is_valid: bool, status: string, message: string, data: ?array}
     */
    public function validateImbPbg(?string $imbPbgNumber, ?string $nib = null): array
    {
        if (empty($imbPbgNumber)) {
            return [
                'is_valid' => false,
                'status' => 'missing_number',
                'message' => 'Nomor IMB/PBG tidak disertakan.',
                'data' => null,
            ];
        }

        if (!$this->enabled || empty($this->baseUrl)) {
            return [
                'is_valid' => true,
                'status' => 'bypassed_service_not_configured',
                'message' => 'Layanan validasi DPMPTSP belum diaktifkan/dikonfigurasi. Status bypass aktif.',
                'data' => null,
            ];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v1/izin/validate", [
                    'nomor_izin' => $imbPbgNumber,
                    'nib' => $nib,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $isValid = (bool) ($data['valid'] ?? false);

                return [
                    'is_valid' => $isValid,
                    'status' => $isValid ? 'verified' : 'invalid',
                    'message' => $isValid ? 'IMB/PBG terverifikasi valid di DPMPTSP.' : 'Nomor IMB/PBG tidak ditemukan atau tidak aktif di database DPMPTSP.',
                    'data' => $data,
                ];
            }

            Log::warning("DPMPTSP API response error ({$response->status()}): " . $response->body());

            // Fail-open
            return [
                'is_valid' => true,
                'status' => 'unverified_service_error',
                'message' => 'Layanan DPMPTSP merespons error (' . $response->status() . '). Diloloskan secara fail-open untuk verifikasi manual.',
                'data' => null,
            ];
        } catch (\Exception $e) {
            Log::warning("DPMPTSP API connection failed: " . $e->getMessage());

            // Fail-open
            return [
                'is_valid' => true,
                'status' => 'unverified_service_unavailable',
                'message' => 'Koneksi ke DPMPTSP offline. Diloloskan secara fail-open untuk verifikasi manual.',
                'data' => null,
            ];
        }
    }
}
