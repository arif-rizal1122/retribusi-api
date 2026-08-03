<?php

namespace App\Jobs;

use App\Models\AutoDeductLog;
use App\Services\Payment\Snap\BankSultraAftDirectDebitService;
use App\Services\Payment\Snap\BankSultraAftReadiness;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExecuteAutoDebitAFT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $autoDeductLogId;

    public function __construct($autoDeductLogId)
    {
        $this->autoDeductLogId = $autoDeductLogId;
    }

    public function handle(?BankSultraAftDirectDebitService $directDebit = null): void
    {
        $directDebit ??= app(BankSultraAftDirectDebitService::class);

        $log = AutoDeductLog::find($this->autoDeductLogId);
        if (! $log || $log->status !== 'pending') {
            return;
        }

        try {
            $readiness = app(BankSultraAftReadiness::class);
            if (! $readiness->available()) {
                $log->update([
                    'status' => 'failed',
                    'escrow_settlement_status' => 'failed',
                    'failure_reason' => $readiness->publicMessage(),
                    'processed_at' => now(),
                ]);

                return;
            }

            $externalId = $this->externalId($log);
            $accessToken = $this->requestAccessToken($directDebit);
            $paymentRequest = $directDebit->buildPaymentRequest(
                [
                    'partner_reference_no' => (string) $log->reference_number,
                    'merchant_id' => $this->merchantId($log),
                    'amount' => (float) $log->deducted_amount,
                    'fee_amount' => 0,
                    'remark' => 'Auto-Debit Pajak PBJT',
                ],
                $accessToken,
                $externalId,
                now()->toIso8601String()
            );

            try {
                $response = Http::timeout((int) config('snap.bank_sultra_aft.timeout_seconds', 15))
                    ->withHeaders($paymentRequest['headers'])
                    ->post($this->snapUrl($paymentRequest['path']), $paymentRequest['body']);
            } catch (ConnectionException $e) {
                $this->recordTimeout($log, $directDebit, $paymentRequest);

                return;
            }

            $responseBody = $response->json() ?? [];
            $parsedResponse = $directDebit->parseResponse($responseBody);
            $this->recordBankResponse($log, $directDebit, $paymentRequest, $parsedResponse, $responseBody);

            if ($parsedResponse['service_code'] !== $this->expectedPaymentServiceCode()) {
                $this->failLog($log, 'Unexpected Bank Sultra AFT service code.');

                return;
            }

            if ($parsedResponse['status'] === 'success') {
                $this->settleLog($log, $responseBody);

                return;
            }

            if ($parsedResponse['status'] === 'pending') {
                $log->update([
                    'status' => 'pending',
                    'escrow_settlement_status' => 'pending',
                    'failure_reason' => $parsedResponse['message'] ?: 'Request In Progress',
                    'processed_at' => null,
                ]);

                return;
            }

            $this->failLog($log, $parsedResponse['message'] ?: 'Bank Sultra AFT failed.');
        } catch (\Exception $e) {
            Log::error('AFT Execution Failed: '.$e->getMessage());
            $log->update([
                'status' => 'failed',
                'escrow_settlement_status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'processed_at' => now(),
            ]);
        }
    }

    private function requestAccessToken(BankSultraAftDirectDebitService $directDebit): string
    {
        $response = Http::timeout((int) config('snap.bank_sultra_aft.timeout_seconds', 15))
            ->withHeaders($directDebit->buildAccessTokenHeaders(now()->toIso8601String()))
            ->post($this->snapUrl('/'.trim((string) config('snap.bank_sultra_aft.version', 'v1.0'), '/').'/access-token/b2b'), [
                'grantType' => 'client_credentials',
            ]);

        $token = $response->json('accessToken') ?? $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new \RuntimeException($response->json('responseMessage') ?? 'Token Bank Sultra AFT tidak tersedia.');
        }

        return $token;
    }

    private function snapUrl(string $path): string
    {
        return rtrim((string) config('snap.bank_sultra_aft.base_url'), '/').'/'.ltrim($path, '/');
    }

    private function externalId(AutoDeductLog $log): string
    {
        $metadata = $this->metadata($log);
        $current = (string) data_get($metadata, 'bank_sultra_aft.external_id', '');
        if ($current !== '') {
            return $current;
        }

        $externalId = 'AFT-'.$log->id.'-'.strtoupper(substr(hash('sha256', (string) $log->reference_number), 0, 16));
        data_set($metadata, 'bank_sultra_aft.external_id', $externalId);
        $log->update(['metadata' => $metadata]);

        return $externalId;
    }

    private function recordBankResponse(
        AutoDeductLog $log,
        BankSultraAftDirectDebitService $directDebit,
        array $paymentRequest,
        array $parsedResponse,
        array $responseBody
    ): void {
        $metadata = $this->metadata($log);
        data_set($metadata, 'bank_sultra_aft.payment_request', $directDebit->maskAuditPayload($paymentRequest));
        data_set($metadata, 'bank_sultra_aft.payment_response', $parsedResponse);
        data_set($metadata, 'bank_sultra_aft.bank_reference_no', $this->bankReferenceNo($responseBody));

        $log->update(['metadata' => $metadata]);
    }

    private function recordTimeout(
        AutoDeductLog $log,
        BankSultraAftDirectDebitService $directDebit,
        array $paymentRequest
    ): void {
        $metadata = $this->metadata($log);
        data_set($metadata, 'bank_sultra_aft.payment_request', $directDebit->maskAuditPayload($paymentRequest));
        data_set($metadata, 'bank_sultra_aft.last_error', 'timeout');

        $log->update([
            'status' => 'pending',
            'escrow_settlement_status' => 'pending',
            'failure_reason' => 'Bank Sultra AFT timeout; akan dicoba ulang.',
            'processed_at' => null,
            'metadata' => $metadata,
        ]);
    }

    private function settleLog(AutoDeductLog $log, array $responseBody): void
    {
        $metadata = $this->metadata($log);
        data_set($metadata, 'bank_sultra_aft.bank_reference_no', $this->bankReferenceNo($responseBody));

        $log->update([
            'status' => 'success',
            'escrow_settlement_status' => 'settled',
            'failure_reason' => null,
            'processed_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function failLog(AutoDeductLog $log, string $reason): void
    {
        $log->update([
            'status' => 'failed',
            'escrow_settlement_status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);
    }

    private function metadata(AutoDeductLog $log): array
    {
        return is_array($log->metadata) ? $log->metadata : [];
    }

    private function merchantId(AutoDeductLog $log): string
    {
        $merchantId = trim((string) data_get($this->metadata($log), 'bank_sultra_aft.merchant_id'));

        return $merchantId !== '' ? $merchantId : (string) $log->taxpayer_id;
    }

    private function bankReferenceNo(array $responseBody): ?string
    {
        foreach (['referenceNo', 'bankReferenceNo', 'transactionId', 'partnerReferenceNo'] as $key) {
            if (isset($responseBody[$key]) && is_string($responseBody[$key]) && $responseBody[$key] !== '') {
                return $responseBody[$key];
            }
        }

        return null;
    }

    private function expectedPaymentServiceCode(): string
    {
        return (string) config('snap.bank_sultra_aft.selected_product') === 'direct_debit_bifast' ? '71' : '54';
    }
}
