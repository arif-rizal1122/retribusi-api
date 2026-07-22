<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SnapTokenService
{
    public function issue(string $clientKey, string $bankCode): array
    {
        $ttl = (int) config('snap.token_ttl_seconds', 900);
        $token = Str::random(80);

        Cache::put($this->cacheKey($token), [
            'client_key' => $clientKey,
            'bank_code' => $bankCode,
        ], now()->addSeconds($ttl));

        return [
            'access_token' => $token,
            'expires_in' => $ttl,
        ];
    }

    public function validateAuthorizationHeader(?string $authorizationHeader, string $expectedBankCode, string $serviceCode = '24'): void
    {
        if (! config('snap.security.require_bearer_token', true)) {
            return;
        }

        if (! $authorizationHeader || ! str_starts_with($authorizationHeader, 'Bearer ')) {
            throw SnapValidationException::invalidToken($serviceCode);
        }

        $token = trim(substr($authorizationHeader, 7));
        $cacheKey = $this->cacheKey($token);

        if ($token === '' || ! Cache::has($cacheKey)) {
            throw SnapValidationException::invalidToken($serviceCode);
        }

        $tokenData = Cache::get($cacheKey);
        if (($tokenData['bank_code'] ?? '') !== $expectedBankCode) {
            throw SnapValidationException::invalidToken($serviceCode);
        }
    }

    private function cacheKey(string $token): string
    {
        return 'snap:b2b-token:'.hash('sha256', $token);
    }
}
