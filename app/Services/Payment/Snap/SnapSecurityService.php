<?php

namespace App\Services\Payment\Snap;

use Illuminate\Http\Request;

class SnapSecurityService
{
    public function __construct(
        private readonly SnapHeaderValidator $headers,
        private readonly SnapTimestampValidator $timestamps,
        private readonly SnapSignatureService $signatures,
        private readonly SnapTokenService $tokens
    ) {}

    public function validateAccessTokenRequest(Request $request): void
    {
        $this->validateAllowedIp($request, '73');
        $this->headers->validateAccessTokenHeaders($request);
        $this->timestamps->validate($request->header('X-TIMESTAMP'), '73');
        $this->signatures->verify($request, '73');
    }

    public function validateTransactionRequest(Request $request, string $serviceCode): void
    {
        $this->validateAllowedIp($request, $serviceCode);
        $this->headers->validateTransactionHeaders($request, $serviceCode);
        $this->timestamps->validate($request->header('X-TIMESTAMP'), $serviceCode);
        $bankCode = $request->attributes->get('snap_bank_code');
        $this->tokens->validateAuthorizationHeader($request->header('Authorization'), (string) $bankCode, $serviceCode);
        $this->signatures->verify($request, $serviceCode);
    }

    private function validateAllowedIp(Request $request, string $serviceCode): void
    {
        $allowedIps = config('snap.allowed_ips', []);

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            throw SnapValidationException::unauthorized('Unauthorized Source IP', $serviceCode);
        }
    }
}
