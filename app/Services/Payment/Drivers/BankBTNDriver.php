<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Utils\BtnSignatureService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankBTNDriver implements PaymentGatewayInterface
{
    private $signatureService;
    private $baseUrl;
    private $partnerServiceId;

    public function __construct(BtnSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
        $this->baseUrl = config('services.btn.base_url', 'https://devapi.btn.co.id');
        $this->partnerServiceId = config('services.btn.partner_id', '99017'); // Example fallback
    }

    public function inquiry(string $billNumber): array
    {
        // For BTN Open API, if BTN calls us as Biller, this method might process the inbound request data.
        // However, usually drivers are used to CALL the bank, but M-PAD interface mixes these.
        // We will just return empty for outgoing since Create VA is used.
        return [];
    }

    public function notify(array $payload): array
    {
        // Process webhook payload from BTN /payment
        // The controller already verified the signature before calling PaymentManager->notify
        // Here we format it for M-PAD Billing service standard.

        $virtualAccountData = $payload['virtualAccountData'] ?? [];
        $billNo = $virtualAccountData['customerNo'] ?? null;
        $paidAmount = $payload['paidAmount']['value'] ?? 0;
        
        return [
            'status' => 'success',
            'bill_number' => $billNo,
            'amount' => $paidAmount,
            'trx_id' => $virtualAccountData['paymentRequestId'] ?? null,
            'payment_date' => $payload['trxDateTime'] ?? now(),
            'raw_response' => $payload,
        ];
    }

    public function reversal(array $payload): array
    {
        return [];
    }

    public function getAccountDetail(string $billNumber): array
    {
        // To get account detail, we create a VA at BTN.
        // 1. Get Token
        $token = $this->signatureService->getAccessToken();

        // 2. Create VA
        $endpoint = '/snap/v1/transfer-va/create-va';
        $timestamp = now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        
        $body = [
            'partnerServiceId' => str_pad($this->partnerServiceId, 8, ' ', STR_PAD_LEFT),
            'customerNo' => $billNumber,
            'virtualAccountNo' => str_pad($this->partnerServiceId, 8, ' ', STR_PAD_LEFT) . $billNumber,
            'virtualAccountName' => 'Wajib Pajak M-PAD',
            'trxId' => uniqid('BTN'),
            'totalAmount' => [
                'value' => "0.00", // Will be filled dynamically by billing system wrapper
                'currency' => "IDR"
            ],
            'virtualAccountTrxType' => "F", // Full
        ];

        $signature = $this->signatureService->generateHmacSignature('POST', $endpoint, $token, $body, $timestamp);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
            'X-PARTNER-ID' => config('services.btn.api_key'),
            'X-EXTERNAL-ID' => uniqid(),
            'CHANNEL-ID' => config('services.btn.channel_id', '00001'),
        ])->post($this->baseUrl . $endpoint, $body);

        if (!$response->successful()) {
            Log::error('BTN Create VA failed', ['response' => $response->json()]);
            throw new \Exception('Gagal membuat VA BTN: ' . $response->body());
        }

        return [
            'virtual_account' => $body['virtualAccountNo'],
            'bank' => 'BTN',
            'name' => $body['virtualAccountName'],
        ];
    }

    public function reconcile(array $transactions): array
    {
        return [];
    }
}
