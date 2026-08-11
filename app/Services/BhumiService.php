<?php

namespace App\Services;

use App\Models\BphtbSubmission;
use App\Models\H2hConnectorLog;
use Illuminate\Support\Facades\Http;

class BhumiService
{
    protected string $baseUrl;
    protected string $token;
    protected string $splpEndpoint;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.bhumi.base_url', '');
        $this->token   = (string) config('services.bhumi.token', '');
        $this->splpEndpoint = (string) config('services.bhumi.splp_endpoint', '');
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

    /**
     * M21-1466 (F3.2): Push webhook status lunas ke BPN (SPLP).
     *
     * Dipanggil setelah pembayaran BPHTB lunas. Bila endpoint SPLP belum
     * dikonfigurasi (PKS BPN belum aktif — target Des 2026), cukup dicatat
     * sebagai not_configured dan TIDAK memblokir alur pembayaran.
     *
     * Payload mengikuti pola pull verification (verify-payment):
     * billing_code / ntpd / nop / nama_wp / amount / paid_at / status=lunas.
     */
    public function notifySplpLunas(BphtbSubmission $submission): array
    {
        if (! $this->available() || $this->splpEndpoint === '') {
            H2hConnectorLog::record(
                'bhumi',
                'not_configured',
                '/api/splp/update',
                'Endpoint SPLP BPN belum dikonfigurasi (menunggu PKS)',
                ['submission_id' => $submission->id]
            );

            return ['available' => false, 'pushed' => false, 'reason' => 'not_configured'];
        }

        $payload = [
            'event'         => 'bphtb.lunas',
            'billing_code'  => $submission->billing_code,
            'ntpd'          => $submission->ntpd,
            'nop'           => $submission->nop,
            'nib'           => $submission->nib,
            'nama_wp'       => $submission->nama_wp,
            'nik_wp'        => $submission->nik_wp,
            'amount'        => (float) ($submission->bill?->amount ?? 0),
            'paid_at'       => $submission->paid_at?->toIso8601String(),
            'status'        => 'lunas',
            'waktu_push'    => now()->toIso8601String(),
        ];

        try {
            $response = Http::asJson()->timeout(20)
                ->withToken($this->token)
                ->post($this->splpEndpoint, $payload);

            $data = $response->json() ?? [];

            if ($response->failed()) {
                H2hConnectorLog::record('bhumi', 'failed', '/api/splp/update', 'HTTP ' . $response->status(), [
                    'submission_id' => $submission->id,
                    'billing_code'  => $submission->billing_code,
                ]);

                return ['available' => true, 'pushed' => false, 'reason' => 'error:http_' . $response->status(), 'data' => $data];
            }

            H2hConnectorLog::record('bhumi', 'ok', '/api/splp/update', 'SPLP lunas terkirim', [
                'submission_id' => $submission->id,
                'billing_code'  => $submission->billing_code,
            ]);

            return ['available' => true, 'pushed' => true, 'reason' => 'ok', 'data' => $data];

        } catch (\Exception $e) {
            H2hConnectorLog::record('bhumi', 'failed', '/api/splp/update', $e->getMessage(), [
                'submission_id' => $submission->id,
                'billing_code'  => $submission->billing_code,
            ]);

            return ['available' => true, 'pushed' => false, 'reason' => 'error:' . $e->getMessage()];
        }
    }
}
