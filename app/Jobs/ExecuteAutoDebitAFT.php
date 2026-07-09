<?php

namespace App\Jobs;

use App\Models\AutoDeductLog;
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
            // Placeholder untuk Endpoint BPD Sultra Direct Debit
            $bpdEndpoint = config('services.bpd.direct_debit_url', 'https://api.banksultra.co.id/snap/v1.0/direct-debit/payment');
            $bpdClientId = config('services.bpd.client_id');
            $bpdSecret = config('services.bpd.secret');

            // Dummy payload sesuai standar SNAP BI
            $payload = [
                'partnerReferenceNo' => $log->reference_number,
                'merchantId' => $log->taxpayer_id,
                'sourceAccountNo' => '1234567890', // Didapat dari relasi Taxpayer/Merchant
                'destinationAccountNo' => '0987654321', // Rekening Kas Daerah
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
            $response = Http::withHeaders([
                'X-TIMESTAMP' => now()->toIso8601String(),
                'X-PARTNER-ID' => $bpdClientId,
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
}
