<?php

namespace App\Jobs;

use App\Models\AutoDeductLog;
use App\Services\Payment\Snap\BankSultraAftReadiness;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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

    public function handle(): void
    {
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

            $bpdEndpoint = $this->paymentEndpoint();
            $bpdClientId = config('snap.partners.BANK_SULTRA.partner_id');

            $payload = [
                'partnerReferenceNo' => $log->reference_number,
                'merchantId' => $log->taxpayer_id,
                'sourceAccountNo' => config('snap.bank_sultra_aft.source_account_no'),
                'destinationAccountNo' => config('snap.bank_sultra_aft.destination_account_no'),
                'amount' => [
                    'value' => number_format($log->deducted_amount, 2, '.', ''),
                    'currency' => 'IDR',
                ],
                'feeAmount' => [
                    'value' => '0.00',
                    'currency' => 'IDR',
                ],
                'remark' => 'Auto-Debit Pajak PBJT',
            ];

            // Panggilan API H2H BPD
            $response = Http::timeout((int) config('snap.bank_sultra_aft.timeout_seconds', 15))->withHeaders([
                'X-TIMESTAMP' => now()->toIso8601String(),
                'X-PARTNER-ID' => $bpdClientId,
                'CHANNEL-ID' => config('snap.partners.BANK_SULTRA.channel_id'),
                // Akan ditambahkan signature generation
            ])->post($bpdEndpoint, $payload);

            if ($response->successful() && $response->json('status') === 'SUCCESS') {
                $log->update([
                    'status' => 'success',
                    'escrow_settlement_status' => 'settled',
                    'processed_at' => now(),
                ]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'failure_reason' => $response->json('responseMessage') ?? 'Unknown error',
                    'processed_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AFT Execution Failed: '.$e->getMessage());
            $log->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'processed_at' => now(),
            ]);
        }
    }

    private function paymentEndpoint(): string
    {
        $product = (string) config('snap.bank_sultra_aft.selected_product');
        $path = $product === 'direct_debit_bifast' ? 'debit/fast-payment' : 'debit/payment-host-to-host';

        return sprintf(
            '%s/%s/%s',
            rtrim((string) config('snap.bank_sultra_aft.base_url'), '/'),
            trim((string) config('snap.bank_sultra_aft.version', 'v1.0'), '/'),
            $path
        );
    }
}
