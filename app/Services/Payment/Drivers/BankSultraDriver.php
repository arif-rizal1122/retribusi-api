<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\TaxObject;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankSultraDriver implements PaymentGatewayInterface
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Inquiry (Bank checks bill detail via bill_number)
     */
    public function inquiry(string $billNumber): array
    {
        $bill = Bill::where('bill_number', $billNumber)->first();

        if (!$bill) {
            return [
                'status' => 'error',
                'message' => 'Tagihan tidak ditemukan',
                'code' => 404
            ];
        }

        if ($bill->status === 'lunas') {
            return [
                'status' => 'error',
                'message' => 'Tagihan sudah lunas',
                'code' => 422
            ];
        }

        // Logic JIT: Recalculate penalty real-time
        $taxObject = $bill->taxObject;
        $penalty = $bill->penalty_amount;
        $total = $bill->amount + $penalty;

        if ($taxObject) {
            $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
            $currentPeriod = $pendingPeriods->firstWhere('period', $bill->period);
            
            if ($currentPeriod) {
                $penalty = $currentPeriod['penalty_amount'];
                $total = $currentPeriod['total_amount'];
            }
        }

        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'bill_number' => $bill->bill_number,
                'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
                'tax_object' => $taxObject ? $taxObject->name : 'N/A',
                'period' => $bill->period,
                'amount_pokok' => (float) $bill->amount,
                'penalty_amount' => (float) $penalty,
                'total_amount' => (float) $total,
                'due_date' => $bill->due_date->toDateTimeString(),
            ]
        ];
    }

    /**
     * Notify (Bank sends payment confirmation)
     */
    public function notify(array $payload): array
    {
        $billNumber = $payload['bill_number'] ?? null;
        $amountPaid = (float) ($payload['amount_paid'] ?? 0);
        $ntb = $payload['transaction_id'] ?? $payload['ntb'] ?? null;

        if (!$billNumber || !$amountPaid || !$ntb) {
            return ['status' => 'error', 'message' => 'Invalid payload', 'code' => 400];
        }

        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        // Idempotency check: Check if NTB already exists in payments
        $exists = Payment::where('reference_number', $ntb)->exists();
        if ($exists) {
            return ['status' => 'error', 'message' => 'Transaksi duplikat (NTB sudah terdaftar)', 'code' => 409];
        }

        return DB::transaction(function () use ($bill, $amountPaid, $ntb, $payload) {
            // Final JIT Sync
            $taxObject = $bill->taxObject;
            $currentPenalty = $bill->penalty_amount;
            if ($taxObject) {
                $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
                $periodData = $pendingPeriods->firstWhere('period', $bill->period);
                if ($periodData) {
                    $currentPenalty = $periodData['penalty_amount'];
                }
            }

            $expectedTotal = (float) ($bill->amount + $currentPenalty);
            
            // Validation: amount must match (No partial payment for H2H yet)
            if (abs($amountPaid - $expectedTotal) > 0.01) {
                return [
                    'status' => 'error', 
                    'message' => "Nominal tidak sesuai. Diharapkan: $expectedTotal, Terbayar: $amountPaid", 
                    'code' => 422
                ];
            }

            // Update Bill
            $bill->update([
                'status' => 'lunas',
                'penalty_at_payment' => $currentPenalty,
                'bank_code' => 'SULTRA'
            ]);

            // Create Payment Record
            $ntpd = 'NTPD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(10));
            $payment = Payment::create([
                'bill_id' => $bill->id,
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $bill->taxpayer_id,
                'transaction_id' => 'H2H-' . time(),
                'reference_number' => $ntb,
                'receipt_number' => $ntpd,
                'payment_method' => 'va',
                'channel' => $payload['channel'] ?? 'Teller',
                'amount' => $amountPaid,
                'status' => 'success',
                'billing_period' => $bill->period ?? Carbon::now()->format('Y-m'),
                'paid_at' => Carbon::now(),
                'raw_callback_data' => $payload
            ]);

            // [HOOK] TTE Automated Signing for Receipt (SSPD/SSRD)
            try {
                $signer = \App\Models\User::whereIn('role', ['super_admin', 'admin', 'kabid_pengawas'])->first();
                if ($signer) {
                    $docService = app(\App\Services\OfficialDocumentService::class);
                    $docService->signDocument('bill', $bill->id, $signer, 'Signed automatically via Bank H2H Callback');
                }
            } catch (\Exception $e) {
                Log::error('H2H TTE Hook Failed:', ['bill' => $bill->bill_number, 'error' => $e->getMessage()]);
                // We don't roll back the payment just because TTE failed
            }

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'ntpd' => $ntpd,
                    'bill_number' => $bill->bill_number,
                    'status' => 'LUNAS'
                ]
            ];
        });
    }

    /**
     * Reversal (Bank cancels payment)
     */
    public function reversal(array $payload): array
    {
        $ntb = $payload['ntb'] ?? $payload['transaction_id'] ?? null;
        
        if (!$ntb) {
            return ['status' => 'error', 'message' => 'Reference number (NTB) required', 'code' => 400];
        }

        $payment = Payment::where('reference_number', $ntb)->first();
        if (!$payment) {
            return ['status' => 'error', 'message' => 'Transaksi tidak ditemukan', 'code' => 404];
        }

        return DB::transaction(function () use ($payment, $payload) {
            $bill = $payment->bill;
            if ($bill) {
                $bill->update(['status' => 'pending']);
            }

            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'reversal_at' => Carbon::now()->toDateTimeString(),
                    'reversal_reason' => $payload['reason'] ?? 'Reversal by Bank'
                ])
            ]);

            return [
                'status' => 'success',
                'code' => 200,
                'message' => 'Reversal berhasil diproses'
            ];
        });
    }
}
