<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Http\Request;

class SnapHeaderValidator
{
    public function validateAccessTokenHeaders(Request $request): void
    {
        $this->requireHeaders($request, ['X-CLIENT-KEY', 'X-TIMESTAMP', 'X-SIGNATURE'], '4007302');

        $clientKey = $request->header('X-CLIENT-KEY');
        $bankCode = $this->resolveBankByClientKey($clientKey);

        if (!$bankCode) {
            throw SnapValidationException::unauthorized('Unauthorized. Invalid Client Key.');
        }

        $request->attributes->set('snap_bank_code', $bankCode);
    }

    public function validateTransactionHeaders(Request $request): void
    {
        $this->requireHeaders($request, ['X-PARTNER-ID', 'X-EXTERNAL-ID', 'X-TIMESTAMP', 'X-SIGNATURE']);

        $partnerId = $request->header('X-PARTNER-ID');
        $bankCode = $this->resolveBankByPartnerId($partnerId);

        if (!$bankCode) {
            throw SnapValidationException::unauthorized('Unauthorized. Invalid Partner ID.');
        }

        $request->attributes->set('snap_bank_code', $bankCode);
    }

    private function requireHeaders(Request $request, array $headers, string $code = '4002402'): void
    {
        foreach ($headers as $header) {
            if (!$request->header($header)) {
                throw SnapValidationException::missing("Bad Request. Missing {$header}.", $code);
            }
        }
    }

    private function resolveBankByClientKey(string $clientKey): ?string
    {
        $partners = config('snap.partners', []);
        foreach ($partners as $code => $config) {
            if (isset($config['client_key']) && $config['client_key'] === $clientKey) {
                return (string) $code;
            }
        }
        return null;
    }

    private function resolveBankByPartnerId(string $partnerId): ?string
    {
        $partners = config('snap.partners', []);
        foreach ($partners as $code => $config) {
            if (isset($config['partner_id']) && $config['partner_id'] === $partnerId) {
                return (string) $code;
            }
        }
        return null;
    }
}
