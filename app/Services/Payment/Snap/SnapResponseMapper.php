<?php

namespace App\Services\Payment\Snap;

class SnapResponseMapper
{
    public function accessToken(string $token, int $expiresIn): array
    {
        return [
            'responseCode' => '2007300',
            'responseMessage' => 'Successful',
            'accessToken' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => (string) $expiresIn,
        ];
    }

    public function brivaInquiry(array $virtualAccountData): array
    {
        return [
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => $virtualAccountData,
        ];
    }

    public function brivaPayment(array $virtualAccountData): array
    {
        return [
            'responseCode' => '2002400',
            'responseMessage' => 'Successful',
            'virtualAccountData' => $virtualAccountData,
        ];
    }

    public function qrisNotify(): array
    {
        return [
            'responseCode' => '2002500',
            'responseMessage' => 'Successful',
        ];
    }

    public function error(string $responseCode, string $responseMessage): array
    {
        return [
            'responseCode' => $responseCode,
            'responseMessage' => $responseMessage,
        ];
    }
}
