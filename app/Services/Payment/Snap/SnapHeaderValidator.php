<?php

namespace App\Services\Payment\Snap;

use App\Services\Payment\Snap\Exceptions\SnapValidationException;
use Illuminate\Http\Request;

class SnapHeaderValidator
{
    public function validateAccessTokenHeaders(Request $request): void
    {
        $this->requireHeaders($request, ['X-CLIENT-KEY', 'X-TIMESTAMP', 'X-SIGNATURE'], '73');

        $clientKey = $request->header('X-CLIENT-KEY');
        $bankCode = $this->resolveBankByClientKey($clientKey);

        if (! $bankCode) {
            throw SnapValidationException::unauthorized('Unauthorized Client Key', '73');
        }

        $request->attributes->set('snap_bank_code', $bankCode);
    }

    public function validateTransactionHeaders(Request $request, string $serviceCode = '24'): void
    {
        $this->requireHeaders($request, ['X-PARTNER-ID', 'X-EXTERNAL-ID', 'X-TIMESTAMP', 'X-SIGNATURE'], $serviceCode);

        $partnerId = $request->header('X-PARTNER-ID');
        $bankCode = $this->resolveBankByPartnerId($partnerId);

        if (! $bankCode) {
            throw SnapValidationException::unauthorized('Unauthorized Partner ID', $serviceCode);
        }

        $request->attributes->set('snap_bank_code', $bankCode);
    }

    private function requireHeaders(Request $request, array $headers, string $serviceCode): void
    {
        foreach ($headers as $header) {
            if (! $request->header($header)) {
                throw SnapValidationException::missing($header, $serviceCode);
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
