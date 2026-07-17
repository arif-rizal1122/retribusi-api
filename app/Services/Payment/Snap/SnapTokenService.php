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

    public function validateAuthorizationHeader(?string $authorizationHeader, string $expectedBankCode): void
    {
        if (!config('snap.security.require_bearer_token', true)) {
            return;
        }

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            throw SnapValidationException::invalidToken('Unauthorized. Missing Bearer Token.');
        }

        $token = trim(substr($authorizationHeader, 7));
        $cacheKey = $this->cacheKey($token);

        if ($token === '' || !Cache::has($cacheKey)) {
            throw SnapValidationException::invalidToken();
        }

        $tokenData = Cache::get($cacheKey);
        if (($tokenData['bank_code'] ?? '') !== $expectedBankCode) {
            throw SnapValidationException::unauthorized('Unauthorized. Token does not belong to the requested Partner ID.');
        }
    }

    private function cacheKey(string $token): string
    {
        return 'snap:b2b-token:' . hash('sha256', $token);
    }
}
