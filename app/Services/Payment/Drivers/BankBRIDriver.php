<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\BillingService;
use App\Services\Payment\Traits\HasMockResponse;

class BankBRIDriver implements PaymentGatewayInterface
{
    use HasMockResponse;

    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function inquiry(string $billNumber): array
    {
        if ($this->isSandbox()) return $this->mockInquiry($billNumber);
        
        $bill = \App\Models\Bill::where('bill_number', $billNumber)->first();

        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }
        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

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

    public function notify(array $payload): array
    {
        if ($this->isSandbox()) return $this->mockNotify($payload);
        
        $billNumber = $payload['customerNo'] ?? $payload['bill_number'] ?? null;
        $amountPaid = (float) ($payload['amount'] ?? $payload['amount_paid'] ?? 0);
        $ntb = $payload['partnerServiceId'] ?? $payload['transaction_id'] ?? null;
        
        if (!$billNumber) {
            return ['status' => 'error', 'message' => 'Invalid payload', 'code' => 400];
        }

        $bill = \App\Models\Bill::where('bill_number', $billNumber)->first();
        if (!$bill) {
            return ['status' => 'error', 'message' => 'Tagihan tidak ditemukan', 'code' => 404];
        }
        if ($bill->status === 'lunas') {
            return ['status' => 'error', 'message' => 'Tagihan sudah lunas', 'code' => 422];
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($bill, $amountPaid, $ntb, $payload) {
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
                'bank_code' => 'BRI'
            ]);

            $ntpd = 'NTPD-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(10));
            $payment = \App\Models\Payment::create([
                'bill_id' => $bill->id,
                'tax_object_id' => $bill->tax_object_id,
                'taxpayer_id' => $bill->taxpayer_id,
                'transaction_id' => 'H2H-BRI-' . time(),
                'reference_number' => $ntb ?? 'BRI-' . time(),
                'receipt_number' => $ntpd,
                'payment_method' => 'va',
                'channel' => $payload['channel'] ?? 'BRI_SNAP',
                'amount' => $bill->amount + $currentPenalty, // Just set fully paid
                'status' => 'success',
                'billing_period' => $bill->period ?? \Carbon\Carbon::now()->format('Y-m'),
                'paid_at' => \Carbon\Carbon::now(),
                'raw_callback_data' => $payload
            ]);
            
            // Automated TTE Signing Hook
            try {
                $signer = \App\Models\User::whereIn('role', ['super_admin', 'admin', 'kabid_pengawas'])->first();
                if ($signer) {
                    $docService = app(\App\Services\OfficialDocumentService::class);
                    $docService->signDocument('bill', $bill->id, $signer, 'Signed automatically via Bank H2H Callback');
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('H2H TTE Hook Failed:', ['bill' => $bill->bill_number, 'error' => $e->getMessage()]);
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

    public function reversal(array $payload): array
    {
        if ($this->isSandbox()) return $this->mockReversal($payload);
        return ['status' => 'error', 'code' => 501, 'message' => 'BRI driver: reversal endpoint belum diimplementasi sepenuhnya.'];
    }

    public function getAccountDetail(string $billNumber): array
    {
        if ($this->isSandbox()) return $this->mockAccountDetail($billNumber);
        return ['status' => 'error', 'code' => 501, 'message' => 'BRI driver: VA generation belum diimplementasi sepenuhnya.'];
    }

    public function reconcile(array $transactions): array
    {
        if ($this->isSandbox()) return $this->mockReconcile($transactions);
        return ['status' => 'error', 'code' => 501, 'message' => 'BRI driver: reconciliation belum diimplementasi sepenuhnya.'];
    }
}