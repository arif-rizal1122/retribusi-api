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
            'event'          => 'bphtb.lunas',
            'billing_code'   => $submission->billing_code,
            'ntpd'           => $submission->ntpd,
            'sspd_number'    => $submission->sspd_number,
            'sspd_issued_at' => $submission->sspd_issued_at?->toIso8601String(),
            'sspd_qr_hash'   => $submission->sspd_qr_hash,
            'nop'            => $submission->nop,
            'nib'            => $submission->nib,
            'nama_wp'        => $submission->nama_wp,
            'nik_wp'         => $submission->nik_wp,
            'amount'         => (float) ($submission->bill?->amount ?? 0),
            'paid_at'        => $submission->paid_at?->toIso8601String(),
            'status'         => 'lunas',
            'waktu_push'     => now()->toIso8601String(),
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

    /**
     * Reverse spatial lookup: menarik data NIB, NOP, ZNT, dan luas legal
     * berdasarkan koordinat GIS / poligon dari MapPicker.
     */
    public function reverseLookupSpatial(float $lat, float $lng, ?array $polygonCoordinates = null): array
    {
        if (! $this->available()) {
            H2hConnectorLog::record(
                'bhumi',
                'not_configured',
                '/api/spatial/reverse-lookup',
                'BHUMI reverse lookup belum dikonfigurasi (menunggu PKS)'
            );

            return [
                'available'          => false,
                'is_matched'         => false,
                'nib'                => null,
                'nop_bpn'            => null,
                'zona_znt'           => null,
                'nir_value'          => null,
                'legal_land_area'    => null,
                'verification_badge' => 'UNVERIFIED_NON_SERTIPIKAT',
                'reason'             => 'not_configured',
                'message'            => 'Layanan H2H BHUMI BPN belum aktif. Formulir dialihkan ke pendaftaran mandiri.',
            ];
        }

        $startTime = microtime(true);

        try {
            $response = Http::asJson()->timeout(15)
                ->withToken($this->token)
                ->post(rtrim($this->baseUrl, '/') . '/api/spatial/reverse-lookup', [
                    'latitude'            => $lat,
                    'longitude'           => $lng,
                    'polygon_coordinates' => $polygonCoordinates,
                ]);

            $elapsedMs = (microtime(true) - $startTime) * 1000;
            if ($elapsedMs > 5000) {
                H2hConnectorLog::record('bhumi', 'latency_warning', '/api/spatial/reverse-lookup', "Response time {$elapsedMs}ms > 5000ms");
            }

            $data = $response->json() ?? [];

            if ($response->failed()) {
                H2hConnectorLog::record('bhumi', 'failed', '/api/spatial/reverse-lookup', 'HTTP ' . $response->status(), [
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

                return [
                    'available'          => true,
                    'is_matched'         => false,
                    'nib'                => null,
                    'verification_badge' => 'UNVERIFIED_NON_SERTIPIKAT',
                    'reason'             => 'error:http_' . $response->status(),
                ];
            }

            return [
                'available'          => true,
                'is_matched'         => (bool) ($data['is_matched'] ?? false),
                'nib'                => $data['nib'] ?? null,
                'nop_bpn'            => $data['nop_bpn'] ?? null,
                'zona_znt'           => $data['zona_znt'] ?? null,
                'nir_value'          => isset($data['nir_value']) ? (float) $data['nir_value'] : null,
                'legal_land_area'    => isset($data['legal_land_area']) ? (float) $data['legal_land_area'] : null,
                'verification_badge' => ! empty($data['nib']) ? 'VERIFIED_BPN_BHUMI' : 'UNVERIFIED_NON_SERTIPIKAT',
                'official_polygon'   => $data['official_polygon'] ?? null,
                'reason'             => 'ok',
            ];

        } catch (\Exception $e) {
            H2hConnectorLog::record('bhumi', 'failed', '/api/spatial/reverse-lookup', $e->getMessage(), [
                'lat' => $lat,
                'lng' => $lng,
            ]);

            return [
                'available'          => true,
                'is_matched'         => false,
                'nib'                => null,
                'verification_badge' => 'UNVERIFIED_NON_SERTIPIKAT',
                'reason'             => 'error:' . $e->getMessage(),
            ];
        }
    }

    /**
     * Query aset/bidang tanah berdasarkan NIK Wajib Pajak ke BHUMI/KKP BPN.
     */
    public function lookupNibByNik(string $nik): array
    {
        if (! $this->available()) {
            return ['available' => false, 'parcels' => [], 'reason' => 'not_configured'];
        }

        try {
            $response = Http::asJson()->timeout(15)
                ->withToken($this->token)
                ->post(rtrim($this->baseUrl, '/') . '/api/taxpayer/parcels-by-nik', [
                    'nik' => $nik,
                ]);

            if ($response->failed()) {
                return ['available' => true, 'parcels' => [], 'reason' => 'error:http_' . $response->status()];
            }

            return [
                'available' => true,
                'parcels'   => $response->json('parcels') ?? [],
                'reason'    => 'ok',
            ];
        } catch (\Exception $e) {
            return ['available' => true, 'parcels' => [], 'reason' => 'error:' . $e->getMessage()];
        }
    }

    /**
     * Ambil data polygon resmi BPN berdasarkan NIB atau NOP.
     */
    public function fetchPolygon(string $nibOrNop): array
    {
        if (! $this->available()) {
            return ['available' => false, 'polygon' => null, 'reason' => 'not_configured'];
        }

        try {
            $response = Http::asJson()->timeout(15)
                ->withToken($this->token)
                ->get(rtrim($this->baseUrl, '/') . '/api/spatial/polygon/' . urlencode($nibOrNop));

            if ($response->failed()) {
                return ['available' => true, 'polygon' => null, 'reason' => 'error:http_' . $response->status()];
            }

            return [
                'available' => true,
                'polygon'   => $response->json('polygon') ?? null,
                'reason'    => 'ok',
            ];
        } catch (\Exception $e) {
            return ['available' => true, 'polygon' => null, 'reason' => 'error:' . $e->getMessage()];
        }
    }
}
