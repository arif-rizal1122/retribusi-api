<?php

namespace App\Services\Payment\Snap;

use InvalidArgumentException;

class BankSultraAftDirectDebitService
{
    public function __construct(
        private readonly SnapCanonicalRequest $canonicalRequest,
        private readonly SensitivePaymentLogMasker $masker
    ) {}

    public function buildAccessTokenHeaders(string $timestamp): array
    {
        $clientKey = (string) config('snap.partners.BANK_SULTRA.client_key');

        return [
            'X-TIMESTAMP' => $timestamp,
            'X-CLIENT-KEY' => $clientKey,
            'X-SIGNATURE' => $this->sign($clientKey.'|'.$timestamp),
        ];
    }

    public function buildPaymentRequest(array $input, string $accessToken, string $externalId, string $timestamp): array
    {
        $body = $this->selectedProduct() === 'direct_debit_bifast'
            ? $this->bifastPaymentBody($input)
            : $this->h2hPaymentBody($input);

        return $this->transactionRequest('POST', $this->operationPath('payment'), $body, $accessToken, $externalId, $timestamp);
    }

    public function buildStatusRequest(string $partnerReferenceNo, string $accessToken, string $externalId, string $timestamp): array
    {
        $body = [
            'originalPartnerReferenceNo' => $partnerReferenceNo,
        ];

        return $this->transactionRequest('POST', $this->operationPath('status'), $body, $accessToken, $externalId, $timestamp);
    }

    public function buildNotifyAcknowledgement(array $body, string $accessToken, string $externalId, string $timestamp): array
    {
        return $this->transactionRequest('POST', $this->operationPath('notify'), $body, $accessToken, $externalId, $timestamp);
    }

    public function parseResponse(array $response): array
    {
        $responseCode = (string) ($response['responseCode'] ?? '');
        if (! preg_match('/^\d{7}$/', $responseCode)) {
            throw new InvalidArgumentException('SNAP responseCode Bank Sultra harus 7 digit.');
        }

        $httpStatus = (int) substr($responseCode, 0, 3);
        $caseCode = substr($responseCode, 5, 2);

        return [
            'response_code' => $responseCode,
            'http_status' => $httpStatus,
            'service_code' => substr($responseCode, 3, 2),
            'case_code' => $caseCode,
            'status' => $this->statusFromCode($httpStatus, $caseCode),
            'message' => (string) ($response['responseMessage'] ?? ''),
            'raw' => $this->maskAuditPayload($response),
        ];
    }

    public function maskAuditPayload(array $payload): array
    {
        return $this->maskAccountNumbers($this->masker->mask($payload));
    }

    public function operationPath(string $operation): string
    {
        $version = trim((string) config('snap.bank_sultra_aft.version', 'v1.0'), '/');
        $product = $this->selectedProduct();

        $paths = [
            'direct_debit_h2h' => [
                'payment' => 'debit/payment-host-to-host',
                'status' => 'debit/status',
                'notify' => 'debit/notify',
            ],
            'direct_debit_bifast' => [
                'payment' => 'debit/fast-payment',
                'notify' => 'debit/fast-notify',
            ],
        ];

        if (! isset($paths[$product][$operation])) {
            throw new InvalidArgumentException("Operasi Bank Sultra AFT {$operation} belum tersedia untuk {$product}.");
        }

        return '/'.$version.'/'.$paths[$product][$operation];
    }

    private function transactionRequest(string $method, string $path, array $body, string $accessToken, string $externalId, string $timestamp): array
    {
        $canonicalBody = $this->canonicalRequest->canonicalBodyFromArray($body);
        $stringToSign = $this->canonicalRequest->stringToSign($method, $path, $canonicalBody, $timestamp);

        return [
            'method' => $method,
            'path' => $path,
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'X-TIMESTAMP' => $timestamp,
                'X-SIGNATURE' => $this->sign($stringToSign),
                'X-PARTNER-ID' => (string) config('snap.partners.BANK_SULTRA.partner_id'),
                'X-EXTERNAL-ID' => $externalId,
                'CHANNEL-ID' => (string) config('snap.partners.BANK_SULTRA.channel_id'),
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
        ];
    }

    private function h2hPaymentBody(array $input): array
    {
        return [
            'partnerReferenceNo' => $this->stringInput($input, 'partner_reference_no', 'partnerReferenceNo'),
            'merchantId' => $this->stringInput($input, 'merchant_id', 'merchantId'),
            'sourceAccountNo' => (string) config('snap.bank_sultra_aft.source_account_no'),
            'destinationAccountNo' => (string) config('snap.bank_sultra_aft.destination_account_no'),
            'amount' => $this->amount($input['amount'] ?? 0),
            'feeAmount' => $this->amount($input['fee_amount'] ?? 0),
            'remark' => (string) ($input['remark'] ?? 'Auto-Debit Pajak'),
        ];
    }

    private function bifastPaymentBody(array $input): array
    {
        return [
            'partnerReferenceNo' => $this->stringInput($input, 'partner_reference_no', 'partnerReferenceNo'),
            'eMandateReffId' => $this->stringInput($input, 'e_mandate_reference_id', 'eMandateReffId'),
            'sourceAccountNo' => (string) config('snap.bank_sultra_aft.source_account_no'),
            'beneficiaryAccountNo' => (string) config('snap.bank_sultra_aft.destination_account_no'),
            'amount' => $this->amount($input['amount'] ?? 0),
            'remark' => (string) ($input['remark'] ?? 'Auto-Debit Pajak'),
        ];
    }

    private function amount(mixed $value): array
    {
        return [
            'value' => number_format((float) $value, 2, '.', ''),
            'currency' => 'IDR',
        ];
    }

    private function sign(string $stringToSign): string
    {
        $algorithm = strtolower((string) config('snap.partners.BANK_SULTRA.signature_algorithm', 'rsa_sha256'));
        if ($algorithm === 'hmac_sha512') {
            return base64_encode(hash_hmac(
                'sha512',
                $stringToSign,
                (string) config('snap.partners.BANK_SULTRA.signature_secret'),
                true
            ));
        }

        $privateKeyPath = (string) config('snap.partners.BANK_SULTRA.private_key_path');
        if ($privateKeyPath === '' || ! is_readable($privateKeyPath)) {
            throw new InvalidArgumentException('Private key Bank Sultra tidak dapat dibaca aplikasi.');
        }

        openssl_sign($stringToSign, $signature, (string) file_get_contents($privateKeyPath), OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    private function selectedProduct(): string
    {
        $product = (string) config('snap.bank_sultra_aft.selected_product', 'direct_debit_h2h');

        return $product !== '' ? $product : 'direct_debit_h2h';
    }

    private function stringInput(array $input, string $snakeKey, string $camelKey): string
    {
        return (string) ($input[$snakeKey] ?? $input[$camelKey] ?? '');
    }

    private function statusFromCode(int $httpStatus, string $caseCode): string
    {
        if ($httpStatus === 202) {
            return 'pending';
        }

        if ($httpStatus >= 200 && $httpStatus < 300 && $caseCode === '00') {
            return 'success';
        }

        if ($httpStatus >= 400) {
            return 'failed';
        }

        return 'unknown';
    }

    private function maskAccountNumbers(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->maskAccountNumbers($value);

                continue;
            }

            if (is_string($value) && $this->isAccountKey((string) $key)) {
                $payload[$key] = $this->maskAccountNo($value);
            }
        }

        return $payload;
    }

    private function isAccountKey(string $key): bool
    {
        $normalized = strtolower($key);

        return str_ends_with($normalized, 'accountno') || str_ends_with($normalized, 'account_no');
    }

    private function maskAccountNo(string $accountNo): string
    {
        $length = strlen($accountNo);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4).substr($accountNo, -4);
    }
}
