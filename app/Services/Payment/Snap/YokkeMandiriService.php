<?php

namespace App\Services\Payment\Snap;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Yokke v2.0 — QRIS MPM Service (Bank Mandiri UAT)
 *
 * Pola acuan: BtnSignatureService (getAccessToken + RSA sign + Cache).
 * Endpoint YOKKE: https://tst.yokke.co.id:7778
 *
 * Dua jenis signature:
 * 1. Access token  : SHA256withRSA( clientKey | timestamp )       PKCS1v15
 * 2. Transaksi     : SHA256withRSA( METHOD:path:sha256(body):ts ) PKCS1v15
 * 3. Notify verify : verifikasi pakai PUBLIC KEY (mtiKey dari credential)
 */
class YokkeMandiriService
{
    private const PARTNER = 'MANDIRI';
    private const CACHE_KEY = 'yokke_mandiri_access_token';
    private const VERSION = 'v2.0';
    private const TOKEN_PATH_PREFIX = '/qr';

    public function __construct(
        private readonly SnapCanonicalRequest $canonicalRequest
    ) {}

    // ------------------------------------------------------------------
    // Konfigurasi
    // ------------------------------------------------------------------

    private function conf(string $key, string $default = ''): string
    {
        return (string) config('snap.partners.' . self::PARTNER . '.' . $key, $default);
    }

    public function baseUrl(): string
    {
        $url = $this->conf('base_url');

        if ($url === '') {
            throw new RuntimeException('MANDIRI base_url belum dikonfigurasi (snap.partners.MANDIRI.base_url).');
        }

        return rtrim($url, '/');
    }

    private function privateKeyContents(): string
    {
        $path = $this->conf('private_key_path');

        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException("Private key Mandiri tidak dapat dibaca: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException("Private key Mandiri kosong: {$path}");
        }

        return $contents;
    }

    private function publicKeyContents(): string
    {
        $path = $this->conf('public_key_path');

        if ($path === '' || ! is_readable($path)) {
            throw new RuntimeException("Public key Mandiri tidak dapat dibaca: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException("Public key Mandiri kosong: {$path}");
        }

        return $contents;
    }

    // ------------------------------------------------------------------
    // Timestamp & signature
    // ------------------------------------------------------------------

    public function timestamp(): string
    {
        return now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
    }

    /**
     * Tanda tangan RSA-SHA256 (PKCS1v15) + base64.
     */
    public function sign(string $stringToSign): string
    {
        $algorithm = strtolower($this->conf('signature_algorithm', 'rsa_sha256'));

        if ($algorithm === 'hmac_sha512') {
            return base64_encode(hash_hmac(
                'sha512',
                $stringToSign,
                $this->conf('client_secret'),
                true
            ));
        }

        $ok = openssl_sign($stringToSign, $signature, $this->privateKeyContents(), OPENSSL_ALGO_SHA256);

        if (! $ok) {
            throw new RuntimeException('Gagal menandatangani stringToSign Mandiri: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    /**
     * stringToSign transaksi SNAP: METHOD:path:lowercase(hex(sha256(body))):timestamp
     */
    public function transactionStringToSign(string $method, string $path, array $body, string $timestamp): string
    {
        $canonicalBody = $this->canonicalRequest->canonicalBodyFromArray($body);

        return $this->canonicalRequest->stringToSign($method, $path, $canonicalBody, $timestamp);
    }

    // ------------------------------------------------------------------
    // Access token
    // ------------------------------------------------------------------

    /**
     * Ambil access token B2B. Token berlaku 1 jam; cache 50 menit (3000 detik).
     * Return array: ['ok' => bool, 'token' => ?string, 'http' => int, 'raw' => string]
     */
    public function fetchAccessToken(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return ['ok' => true, 'token' => $cached, 'http' => 0, 'raw' => '{"cached":true}'];
        }

        $timestamp = $this->timestamp();
        $clientKey = $this->conf('client_key');
        $stringToSign = $clientKey . '|' . $timestamp;
        $signature = $this->sign($stringToSign);

        $url = $this->baseUrl() . self::TOKEN_PATH_PREFIX . '/' . self::VERSION . '/access-token/b2b';
        $payload = ['grantType' => 'client_credentials'];

        try {
            $response = Http::withHeaders([
                'X-CLIENT-KEY' => $clientKey,
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(30)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error('YOKKE Mandiri token exception', ['url' => $url, 'error' => $e->getMessage()]);

            return ['ok' => false, 'token' => null, 'http' => 0, 'raw' => 'EXCEPTION: ' . $e->getMessage()];
        }

        $http = $response->status();
        $raw = $response->body();

        if (! $response->successful()) {
            Log::error('YOKKE Mandiri token gagal', [
                'http' => $http,
                'url' => $url,
                'body' => substr($raw, 0, 1000),
            ]);

            return ['ok' => false, 'token' => null, 'http' => $http, 'raw' => $raw];
        }

        $token = $response->json('accessToken')
            ?? $response->json('access_token')
            ?? data_get($response->json(), 'additionalInfo.accessToken');

        if (! is_string($token) || $token === '') {
            return ['ok' => false, 'token' => null, 'http' => $http, 'raw' => $raw];
        }

        Cache::put(self::CACHE_KEY, $token, now()->addSeconds(3000));

        return ['ok' => true, 'token' => $token, 'http' => $http, 'raw' => $raw];
    }

    /**
     * Access token atau throw kalau gagal.
     */
    public function accessToken(): string
    {
        $result = $this->fetchAccessToken();

        if (! $result['ok']) {
            throw new RuntimeException(
                'Gagal ambil access token YOKKE Mandiri. HTTP ' . $result['http']
                . ' — ' . substr($result['raw'], 0, 500)
            );
        }

        return $result['token'];
    }

    // ------------------------------------------------------------------
    // Header transaksi
    // ------------------------------------------------------------------

    /**
     * Bangun header transaksi SNAP.
     */
    public function transactionHeaders(string $method, string $path, array $body, string $accessToken, string $externalId, string $timestamp): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $this->sign($this->transactionStringToSign($method, $path, $body, $timestamp)),
            'X-PARTNER-ID' => $this->conf('partner_id'),
            'X-EXTERNAL-ID' => $externalId,
            'CHANNEL-ID' => $this->conf('channel_id'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * X-EXTERNAL-ID wajib unik dalam 1 hari.
     */
    public function generateExternalId(string $prefix = 'MPAD'): string
    {
        return $prefix . date('YmdHis') . random_int(1000, 9999);
    }

    // ------------------------------------------------------------------
    // Operasi QRIS MPM
    // ------------------------------------------------------------------

    public function generateQr(array $input, ?string $externalId = null): array
    {
        $path = '/' . self::VERSION . '/qr/qr-mpm-generate';
        $timestamp = $this->timestamp();
        $externalId = $externalId ?: $this->generateExternalId();

        $body = [
            'partnerReferenceNo' => (string) ($input['partner_reference_no'] ?? $input['partnerReferenceNo'] ?? ''),
            'amount' => [
                'value' => number_format((float) ($input['amount'] ?? 0), 2, '.', ''),
                'currency' => (string) ($input['currency'] ?? 'IDR'),
            ],
            'merchantId' => (string) ($input['merchant_id'] ?? $this->conf('merchant_id')),
            'terminalId' => (string) ($input['terminal_id'] ?? $this->conf('terminal_id')),
            'validityPeriod' => (string) ($input['validity_period']
                ?? now()->timezone('Asia/Jakarta')->addMinutes(30)->format('Y-m-d\TH:i:sP')),
        ];

        if (isset($input['additional_info'])) {
            $body['additionalInfo'] = $input['additional_info'];
        }

        $headers = $this->transactionHeaders('POST', $path, $body, $this->accessToken(), $externalId, $timestamp);

        return $this->send('POST', $path, $body, $headers, $externalId);
    }

    public function queryQr(string $originalReferenceNo, string $originalPartnerReferenceNo, ?string $externalId = null): array
    {
        $path = '/' . self::VERSION . '/qr/qr-mpm-query';
        $timestamp = $this->timestamp();
        $externalId = $externalId ?: $this->generateExternalId();

        $body = [
            'originalReferenceNo' => $originalReferenceNo,
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
            'merchantId' => $this->conf('merchant_id'),
        ];

        $headers = $this->transactionHeaders('POST', $path, $body, $this->accessToken(), $externalId, $timestamp);

        return $this->send('POST', $path, $body, $headers, $externalId);
    }

    public function cancelQr(string $originalReferenceNo, string $originalPartnerReferenceNo, string $reason, ?string $externalId = null): array
    {
        $path = '/' . self::VERSION . '/qr/qr-mpm-cancel';
        $timestamp = $this->timestamp();
        $externalId = $externalId ?: $this->generateExternalId();

        $body = [
            'originalReferenceNo' => $originalReferenceNo,
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
            'reason' => $reason,
            'merchantId' => $this->conf('merchant_id'),
        ];

        $headers = $this->transactionHeaders('POST', $path, $body, $this->accessToken(), $externalId, $timestamp);

        return $this->send('POST', $path, $body, $headers, $externalId);
    }

    private function send(string $method, string $path, array $body, array $headers, string $externalId): array
    {
        $url = $this->baseUrl() . $path;

        try {
            $response = Http::withHeaders($headers)->timeout(40)->send($method, $url, ['json' => $body]);
        } catch (\Throwable $e) {
            Log::error('YOKKE Mandiri request exception', ['url' => $url, 'error' => $e->getMessage()]);

            return [
                'ok' => false,
                'http' => 0,
                'raw' => 'EXCEPTION: ' . $e->getMessage(),
                'json' => null,
                'external_id' => $externalId,
                'path' => $path,
            ];
        }

        $http = $response->status();
        $raw = $response->body();

        if (! $response->successful()) {
            Log::error('YOKKE Mandiri request gagal', [
                'url' => $url,
                'http' => $http,
                'external_id' => $externalId,
                'body' => substr($raw, 0, 1000),
            ]);
        }

        return [
            'ok' => $response->successful(),
            'http' => $http,
            'raw' => $raw,
            'json' => $response->json(),
            'external_id' => $externalId,
            'path' => $path,
        ];
    }

    // ------------------------------------------------------------------
    // Verifikasi signature notify (inbound)
    // ------------------------------------------------------------------

    /**
     * Verifikasi signature payment notify dari YOKKE.
     * stringToSign = POST:{endpointUrl}:{lowercase(hex(sha256(minify(body))))}:{timestamp}
     * Diverifikasi dengan PUBLIC KEY (mtiKey dari credential).
     */
    public function verifyNotifySignature(string $rawBody, string $signature, string $timestamp, string $endpointUrl): bool
    {
        if ($signature === '' || $timestamp === '') {
            return false;
        }

        $decoded = json_decode($rawBody, true);
        $canonicalBody = (json_last_error() === JSON_ERROR_NONE)
            ? $this->canonicalRequest->canonicalBodyFromArray($decoded)
            : $rawBody;

        $stringToSign = 'POST:' . $endpointUrl . ':' . strtolower(hash('sha256', $canonicalBody)) . ':' . $timestamp;

        $publicKey = openssl_pkey_get_public($this->publicKeyContents());

        if ($publicKey === false) {
            Log::error('YOKKE Mandiri: public key tidak valid');

            return false;
        }

        $result = openssl_verify($stringToSign, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }
}