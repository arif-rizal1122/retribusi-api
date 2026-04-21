<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QRISDriver implements PaymentGatewayInterface
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Inquiry (Get Dynamic QRIS for a bill)
     */
    public function inquiry(string $billNumber): array
    {
        $bill = Bill::where('bill_number', $billNumber)->first();

        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        // Recalculate penalty JIT
        $taxObject = $bill->taxObject;
        $penalty = $bill->penalty_amount;
        if ($taxObject) {
            $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
            $currentPeriod = $pendingPeriods->firstWhere('period', $bill->period);
            if ($currentPeriod) {
                $penalty = $currentPeriod['penalty_amount'];
            }
        }

        $total = (float) ($bill->amount + $penalty);

        // Mock QRIS Generation Logic (EMVCo)
        // In real world, this would call a QRIS aggregator API (e.g. Bank Sultra / Netzme)
        $qrString = "00020101021226650016ID.CO.QRIS.WWW01189360000000000000000215" . str_pad((int)($total * 100), 12, "0", STR_PAD_LEFT) . "5802ID5912KOTA BAUBAU6006BAUBAU6304" . strtoupper(Str::random(4));

        return [
            'status' => 'success',
            'code' => 200,
            'data' => [
                'bill_number' => $bill->bill_number,
                'taxpayer_name' => $bill->taxpayer->name ?? 'N/A',
                'amount' => $total,
                'qr_string' => $qrString,
                'expiry_at' => Carbon::now()->addMinutes(30)->toDateTimeString(),
            ]
        ];
    }

    /**
     * Notify (QRIS Payment Success Callback)
     */
    public function notify(array $payload): array
    {
        $billNumber = $payload['bill_number'] ?? null;
        $amountPaid = (float) ($payload['amount_paid'] ?? 0);
        $rrn = $payload['rrn'] ?? $payload['transaction_id'] ?? null;

        if (!$billNumber || !$amountPaid || !$rrn) {
            return ['status' => 'error', 'message' => 'Invalid QRIS payload', 'code' => 400];
        }

        $bill = Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }

        return DB::transaction(function () use ($bill, $amountPaid, $rrn, $payload) {
            if ($bill->status === 'lunas') {
                return ['status' => 'success', 'message' => 'Tagihan sudah lunas (Idempotent)', 'code' => 200];
            }

            // Final Snapshot Penalty
            $taxObject = $bill->taxObject;
            $currentPenalty = $bill->penalty_amount;
            if ($taxObject) {
                $pendingPeriods = $this->billingService->getPendingPeriods($taxObject);
                $periodData = $pendingPeriods->firstWhere('period', $bill->period);
                if ($periodData) {
                    $currentPenalty = $periodData['penalty_amount'];
                }
            }

            $bill->update([
                'status' => 'lunas',
                'penalty_at_payment' => $currentPenalty,
                'bank_code' => 'QRIS'
            ]);

            $ntpd = 'NTPD-QRIS-' . date('Ymd') . '-' . strtoupper(Str::random(8));
            Payment::create([
                'bill_id' => $bill->id,
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $bill->taxpayer_id,
                'transaction_id' => $rrn,
                'reference_number' => $rrn,
                'receipt_number' => $ntpd,
                'payment_method' => 'qris',
                'channel' => 'Mobile Banking / E-Wallet',
                'amount' => $amountPaid,
                'status' => 'success',
                'billing_period' => $bill->period ?? Carbon::now()->format('Y-m'),
                'paid_at' => Carbon::now(),
                'raw_callback_data' => $payload
            ]);

            // [HOOK] TTE Automated Signing
            try {
                $signer = \App\Models\User::whereIn('role', ['super_admin', 'admin', 'kabid_pengawas'])->first();
                if ($signer) {
                    $docService = app(\App\Services\OfficialDocumentService::class);
                    $docService->signDocument('bill', $bill->id, $signer, 'Signed automatically via QRIS Callback');
                }
            } catch (\Exception $e) {
                Log::error('QRIS TTE Hook Failed:', ['bill' => $bill->bill_number, 'error' => $e->getMessage()]);
            }

            return [
                'status' => 'success',
                'code' => 200,
                'data' => [
                    'ntpd' => $ntpd,
                    'status' => 'LUNAS'
                ]
            ];
        });
    }

    /**
     * Reversal (QRIS typically doesn't support reversal via callback, but we implement interface)
     */
    public function reversal(array $payload): array
    {
        return ['status' => 'error', 'message' => 'QRIS does not support automated reversal', 'code' => 405];
    }

    /**
     * Get account details (QR String) for a bill
     */
    public function getAccountDetail(string $billNumber): array
    {
        return $this->inquiry($billNumber);
    }

    /**
     * Reconcile daily transactions with QRIS aggregate report
     */
    public function reconcile(array $transactions): array
    {
        $results = ['matched' => 0, 'mismatch' => 0, 'details' => []];

        foreach ($transactions as $tx) {
            $rrn = $tx['rrn'] ?? $tx['transaction_id'] ?? null;
            $amount = (float) ($tx['amount'] ?? 0);
            
            $payment = Payment::where('reference_number', $rrn)->first();
            
            if ($payment && abs((float)$payment->amount - $amount) < 0.01) {
                $results['matched']++;
            } else {
                $results['mismatch']++;
                $results['details'][] = [
                    'rrn' => $rrn,
                    'report_amount' => $amount,
                    'system_amount' => $payment ? $payment->amount : 0,
                    'status' => $payment ? 'Nominal Berbeda' : 'Tidak Ditemukan'
                ];
            }
        }

        return ['status' => 'success', 'data' => $results];
    }
}
