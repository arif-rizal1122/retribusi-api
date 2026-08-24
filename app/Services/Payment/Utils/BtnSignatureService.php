<?php

namespace App\Services\Payment\Utils;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\Payment\Snap\Exceptions\SnapValidationException;

class BtnSignatureService
{
    private $clientId;
    private $clientSecret;
    private $privateKeyPath;
    private $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.btn.client_id');
        $this->clientSecret = config('services.btn.client_secret');
        $this->privateKeyPath = config('services.btn.private_key_path', storage_path('app/keys/btn_private.pem'));
        $this->baseUrl = config('services.btn.base_url', 'https://devapi.btn.co.id');
    }

    public function getAccessToken(): string
    {
        return Cache::remember('btn_access_token', 800, function () { // 800 seconds cache (BTN expires in 900)
            $endpoint = '/snap/v1/access-token/b2b';
            $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
            $stringToSign = $this->clientId . '|' . $timestamp;
            $signature = $this->generateRsaSignature($stringToSign);

            $response = Http::withHeaders([
                'X-CLIENT-KEY' => $this->clientId,
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $signature,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . $endpoint, [
                'grantType' => 'client_credentials',
                'additionalInfo' => (object)[]
            ]);

            if ($response->successful()) {
                return $response->json('accessToken');
            }

            throw new \Exception('Failed to retrieve BTN Access Token: ' . $response->body());
        });
    }

    public function generateRsaSignature(string $stringToSign): string
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new \Exception('BTN Private Key not found at ' . $this->privateKeyPath);
        }

        $privateKey = file_get_contents($this->privateKeyPath);
        $signature = '';
        openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public function generateHmacSignature(string $method, string $endpointUrl, string $accessToken, array $body, string $timestamp): string
    {
        $minifyBody = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (empty($body)) {
            $minifyBody = '';
        }

        $hashBody = strtolower(hash('sha256', $minifyBody));
        
        // Format: HTTPMethod + ":" + EndpointUrl + ":" + AccessToken + ":" + Lowercase(HexEncode(SHA-256(minify(RequestBody)))) + ":" + X-TIMESTAMP
        $stringToSign = strtoupper($method) . ':' . $endpointUrl . ':' . $accessToken . ':' . $hashBody . ':' . $timestamp;

        return base64_encode(hash_hmac('sha512', $stringToSign, $this->clientSecret, true));
    }

    public function verifyHmacSignature(string $method, string $endpointUrl, string $accessToken, string $rawBody, string $timestamp, string $providedSignature): bool
    {
        $hashBody = strtolower(hash('sha256', $rawBody));
        $stringToSign = strtoupper($method) . ':' . $endpointUrl . ':' . $accessToken . ':' . $hashBody . ':' . $timestamp;
        $expectedSignature = base64_encode(hash_hmac('sha512', $stringToSign, $this->clientSecret, true));
        
        return hash_equals($expectedSignature, $providedSignature);
    }
}
