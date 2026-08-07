<?php

namespace App\Services;

use App\Models\H2hConnectorLog;
use Illuminate\Support\Facades\Http;

class BhumiService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.bhumi.base_url', '');
        $this->token   = (string) config('services.bhumi.token', '');
    }

    /**
     * Kerangka H2H BPN/BHUMI siap dipakai begitu PKS selesai:
     * isi BHUMI_BASE_URL / BHUMI_TOKEN di .env.
     */
    public function available(): bool
    {
        return $this->baseUrl !== '';
    }

    /**
     * Matching NIB–NOP via BHUMI.
     * Default: not_configured → mapping tetap pending, tidak memblokir alur.
     */
    public function matchNibNop(string $nib, ?string $nop = null): array
    {
        if (! $this->available()) {
            H2hConnectorLog::record(
                'bhumi',
                'not_configured',
                '/api/nib-nop/match',
                'BHUMI belum dikonfigurasi (menunggu PKS)'
            );

            return ['available' => false, 'match' => null, 'reason' => 'not_configured'];
        }

        try {
            $response = Http::asJson()->timeout(20)
                ->withToken($this->token)
                ->post(rtrim($this->baseUrl, '/') . '/api/nib-nop/match', [
                    'nib' => $nib,
                    'nop' => $nop,
                ]);

            $data = $response->json() ?? [];

            if ($response->failed()) {
                H2hConnectorLog::record('bhumi', 'failed', '/api/nib-nop/match', 'HTTP ' . $response->status(), ['nib' => $nib]);

                return ['available' => true, 'match' => null, 'reason' => 'error:http_' . $response->status()];
            }

            return [
                'available' => true,
                'match'     => $data['match'] ?? null,
                'reason'    => $data['reason'] ?? 'ok',
                'data'      => $data,
            ];

        } catch (\Exception $e) {
            H2hConnectorLog::record('bhumi', 'failed', '/api/nib-nop/match', $e->getMessage(), ['nib' => $nib]);

            return ['available' => true, 'match' => null, 'reason' => 'error:' . $e->getMessage()];
        }
    }
}
