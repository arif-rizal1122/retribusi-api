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
    ) {
    }

    public function validateAccessTokenRequest(Request $request): void
    {
        $this->validateAllowedIp($request);
        $this->headers->validateAccessTokenHeaders($request);
        $this->timestamps->validate($request->header('X-TIMESTAMP'));
        $this->signatures->verify($request);
    }

    public function validateTransactionRequest(Request $request): void
    {
        $this->validateAllowedIp($request);
        $this->headers->validateTransactionHeaders($request);
        $this->timestamps->validate($request->header('X-TIMESTAMP'));
        $bankCode = $request->attributes->get('snap_bank_code');
        $this->tokens->validateAuthorizationHeader($request->header('Authorization'), (string) $bankCode);
        $this->signatures->verify($request);
    }

    private function validateAllowedIp(Request $request): void
    {
        $allowedIps = config('snap.allowed_ips', []);

        if ($allowedIps !== [] && !in_array($request->ip(), $allowedIps, true)) {
            throw SnapValidationException::unauthorized('Unauthorized. Source IP is not allowed.');
        }
    }
}
