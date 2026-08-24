<?php

namespace App\Services\Payment\Snap;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BriStatusInquiryService
 *
 * Outbound inquiry status Virtual Account BRI (SNAP BI).
 * Dipakai saat refresh payment request: mengecek ke BRI apakah VA sudah
 * dibayar, sehingga status "paid" akurat tanpa harus menunggu callback.
 *
 * Autentikasi: HMAC-SHA512 (client_secret) — sesuai config BRI.
 * - Access token : X-SIGNATURE = HMAC-SHA512(clientKey|timestamp)
 * - Transaksi    : X-SIGNATURE = HMAC-SHA512(method:path:accessToken:sha256(body):timestamp)
 */
class BriStatusInquiryService
{
    private string $bankCode = 'BRI';

    private function config(string $key, mixed $default = null): mixed
    {
        return config("snap.partners.{$this->bankCode}.{$key}", $default);
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config('base_url', ''), '/');
    }

    private function clientKey(): string
    {
        return (string) $this->config('client_key', '');
    }

    private function clientSecret(): string
    {
        return (string) $this->config('client_secret', '');
    }

    private function partnerId(): string
    {
        return (string) $this->config('partner_id', '');
    }

    private function timestamp(): string
    {
        return now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
    }

    private function hmac(string $stringToSign): string
    {
        return base64_encode(hash_hmac('sha512', $stringToSign, $this->clientSecret(), true));
    }

    private function rsaSign(string $stringToSign): string
    {
        $keyPath = (string) $this->config('private_key_path', storage_path('app/keys/mpad_bri_private.pem'));
        if (! is_file($keyPath)) {
            throw new \RuntimeException('BRI private key tidak ditemukan: '.$keyPath);
        }

        $signature = '';
        $result = openssl_sign($stringToSign, $signature, file_get_contents($keyPath), OPENSSL_ALGO_SHA256);
        if (! $result) {
            throw new \RuntimeException('BRI RSA sign gagal.');
        }

        return base64_encode($signature);
    }

    /**
     * Signature sesuai algoritma config BRI (rsa_sha256 default outbound; hmac_sha512 fallback).
     */
    private function sign(string $stringToSign): string
    {
        $algorithm = strtolower((string) $this->config('signature_algorithm', 'rsa_sha256'));

        return $algorithm === 'hmac_sha512' ? $this->hmac($stringToSign) : $this->rsaSign($stringToSign);
    }

    /**
     * Dapatkan access token B2B BRI (cache 800s).
     */
    public function getAccessToken(): string
    {
        return Cache::remember('bri_status_inquiry_token', 800, function () {
            $timestamp = $this->timestamp();
            $stringToSign = $this->clientKey().'|'.$timestamp;
            $signature = $this->sign($stringToSign);

            $response = Http::withHeaders([
                'X-CLIENT-KEY' => $this->clientKey(),
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl().'/v1.1/access-token/b2b', [
                'grantType' => 'client_credentials',
                'additionalInfo' => (object) [],
            ]);

            if ($response->successful()) {
                $token = (string) $response->json('accessToken', '');
                if ($token !== '') {
                    return $token;
                }
            }

            Log::warning('BRI status inquiry: gagal ambil access token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('BRI access token gagal: '.$response->status().' '.Str::limit($response->body(), 300));
        });
    }

    /**
     * Inquiry status VA ke BRI.
     *
     * @return array{status: 'paid'|'pending'|'expired'|'error', raw?: array, message?: string}
     */
    public function inquiryStatus(string $vaNumber, string $partnerServiceId): array
    {
        $base = $this->baseUrl();
        if ($base === '') {
            return ['status' => 'error', 'message' => 'BRI_SNAP_BASE_URL belum dikonfigurasi.'];
        }

        if (! (bool) config('snap.briva.inquiry_enabled', false)) {
            return ['status' => 'error', 'message' => 'BRI status inquiry belum diaktifkan (BRI_SNAP_INQUIRY_ENABLED=false).'];
        }

        try {
            $accessToken = $this->getAccessToken();

            $customerNo = $vaNumber;
            if ($partnerServiceId !== '' && str_starts_with($vaNumber, $partnerServiceId)) {
                $customerNo = substr($vaNumber, strlen($partnerServiceId));
            }

            $path = '/v1.0/transfer-va/status';
            $timestamp = $this->timestamp();
            $externalId = (string) Str::uuid();

            $body = [
                'partnerServiceId' => $partnerServiceId,
                'customerNo' => $customerNo,
                'virtualAccountNo' => $vaNumber,
                'inquiryRequestId' => $externalId,
                'additionalInfo' => (object) [],
            ];

            $minifyBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $hashBody = strtolower(hash('sha256', (string) $minifyBody));
            $stringToSign = strtoupper('POST').':'.$path.':'.$accessToken.':'.$hashBody.':'.$timestamp;
            $signature = $this->sign($stringToSign);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'X-PARTNER-ID' => $this->partnerId(),
                'X-EXTERNAL-ID' => $externalId,
                'CHANNEL-ID' => '6012',
            ])->post($base.$path, $body);

            $raw = $response->json();

            if (! $response->successful()) {
                Log::warning('BRI status inquiry: response error', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                    'va' => $vaNumber,
                ]);

                return [
                    'status' => 'error',
                    'message' => 'BRI response '.$response->status(),
                    'raw' => is_array($raw) ? $raw : ['raw_body' => Str::limit($response->body(), 500)],
                ];
            }

            return $this->mapStatus($raw, $vaNumber);
        } catch (\Throwable $e) {
            Log::warning('BRI status inquiry: exception', [
                'va' => $vaNumber,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Map response SNAP transfer-va/status ke status netral.
     */
    private function mapStatus(array $raw, string $vaNumber): array
    {
        $vaData = $raw['virtualAccountData'] ?? $raw['data'] ?? $raw;
        if (! is_array($vaData)) {
            $vaData = $raw;
        }

        $paymentFlagStatus = (string) ($vaData['paymentFlagStatus'] ?? $vaData['inquiryStatus'] ?? '');
        $paidAmount = (float) ($vaData['paidAmount'] ?? 0);
        $amountValue = (float) ($vaData['totalAmount'] ?? $vaData['amount'] ?? 0);
        $responseCode = (string) ($raw['responseCode'] ?? '');

        $status = 'pending';
        if ($paymentFlagStatus === '00' || $paidAmount > 0) {
            $status = 'paid';
        } elseif (str_starts_with($responseCode, '403') || $paymentFlagStatus === '07') {
            $status = 'expired';
        }

        return [
            'status' => $status,
            'raw' => $raw,
            'payment_flag_status' => $paymentFlagStatus,
            'paid_amount' => $paidAmount,
            'total_amount' => $amountValue,
            'reference_no' => (string) ($vaData['referenceNo'] ?? $vaData['referenceNumber'] ?? ''),
            'trx_datetime' => (string) ($vaData['trxDateTime'] ?? ''),
        ];
    }
}
