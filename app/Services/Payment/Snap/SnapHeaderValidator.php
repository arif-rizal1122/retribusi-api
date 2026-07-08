<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Http\Request;

class SnapHeaderValidator
{
    public function validateAccessTokenHeaders(Request $request): void
    {
        $this->requireHeaders($request, ['X-CLIENT-KEY', 'X-TIMESTAMP', 'X-SIGNATURE'], '4007302');

        $configuredClientKey = config('snap.client_key');
        if ($configuredClientKey && $request->header('X-CLIENT-KEY') !== $configuredClientKey) {
            throw SnapValidationException::unauthorized('Unauthorized. Invalid Client Key.');
        }
    }

    public function validateTransactionHeaders(Request $request): void
    {
        $this->requireHeaders($request, ['X-PARTNER-ID', 'X-EXTERNAL-ID', 'X-TIMESTAMP', 'X-SIGNATURE']);

        $configuredPartnerId = config('snap.partner_id');
        if ($configuredPartnerId && $request->header('X-PARTNER-ID') !== $configuredPartnerId) {
            throw SnapValidationException::unauthorized('Unauthorized. Invalid Partner ID.');
        }
    }

    private function requireHeaders(Request $request, array $headers, string $code = '4002402'): void
    {
        foreach ($headers as $header) {
            if (!$request->header($header)) {
                throw SnapValidationException::missing("Bad Request. Missing {$header}.", $code);
            }
        }
    }
}
