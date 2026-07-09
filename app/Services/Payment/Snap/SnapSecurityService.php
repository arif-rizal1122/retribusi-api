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
        $this->headers->validateAccessTokenHeaders($request);
        $this->timestamps->validate($request->header('X-TIMESTAMP'));
        $this->signatures->verify($request);
    }

    public function validateTransactionRequest(Request $request): void
    {
        $this->headers->validateTransactionHeaders($request);
        $this->timestamps->validate($request->header('X-TIMESTAMP'));
        $this->tokens->validateAuthorizationHeader($request->header('Authorization'));
        $this->signatures->verify($request);
    }
}
