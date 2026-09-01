<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\Payment\Traits\HasMockResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankBNIDriver implements PaymentGatewayInterface
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
        return ['status' => 'error', 'code' => 501, 'message' => 'BNI driver: production endpoint belum diimplementasi. Siap setelah sandbox BNI API aktif.'];
    }

    /**
     * Proses payment notification dari BNI (VA Billing).
     * Di sandbox: tetap settle bill + catat payment ke DB (pola sama seperti production,
     * supaya uji fungsional Client Simulator ASPI benar-benar end-to-end).
     */
    public function notify(array $payload): array
    {
        if ($this->isSandbox()) {
            return $this->processPaymentNotification($payload);
        }
        return ['status' => 'error', 'code' => 501, 'message' => 'BNI driver: production endpoint belum diimplementasi.'];
    }

    public function reversal(array $payload): array
    {
        if ($this->isSandbox()) return $this->mockReversal($payload);
        return ['status' => 'error', 'code' => 501, 'message' => 'BNI driver: reversal endpoint belum diimplementasi.'];
    }

    public function getAccountDetail(string $billNumber): array
    {
        if ($this->isSandbox()) return $this->mockAccountDetail($billNumber);
        return ['status' => 'error', 'code' => 501, 'message' => 'BNI driver: VA generation belum diimplementasi.'];
    }

    public function reconcile(array $transactions): array
    {
        if ($this->isSandbox()) return $this->mockReconcile($transactions);
        return ['status' => 'error', 'code' => 501, 'message' => 'BNI driver: reconciliation belum diimplementasi.'];
    }

    /**
     * Inti pemrosesan payment notification BNI:
     * 1. Cari bill by customerNo (bill_number) — status open
     * 2. Validasi amount bayar
     * 3. DB transaction: update bill lunas + insert Payment
     */
    private function processPaymentNotification(array $payload): array
    {
        try {
            $customerNo = $payload['customerNo'] ?? null;
            $virtualAccountNo = $payload['virtualAccountNo'] ?? null;
            $paidAmount = (float) ($payload['paidAmount']['value'] ?? 0);
            $trxId = $payload['trxId'] ?? null;
            $referenceNo = $payload['referenceNo'] ?? null;
            $trxDateTime = $payload['trxDateTime'] ?? now()->toDateTimeString();
            $paymentRequestId = $payload['paymentRequestId'] ?? null;

            if (! $customerNo) {
                return ['status' => 'error', 'code' => 400, 'message' => 'customerNo wajib diisi'];
            }

            // Cari bill by customerNo (bill_number) atau VA number
            $bill = Bill::where('bill_number', $customerNo)
                ->whereIn('status', ['pending', 'unpaid', 'waiting_payment'])
                ->orderByDesc('id')
                ->first();

            if (! $bill) {
                Log::warning('BNI Payment: bill not found', ['customerNo' => $customerNo, 'va' => $virtualAccountNo]);
                return ['status' => 'error', 'code' => 404, 'message' => 'Bill not found / sudah lunas'];
            }

            $expectedAmount = $this->expectedAmount($bill);
            if (abs($paidAmount - $expectedAmount) > 0.009) {
                Log::warning('BNI Payment: amount mismatch', [
                    'bill' => $bill->bill_number,
                    'expected' => $expectedAmount,
                    'paid' => $paidAmount,
                ]);
                return ['status' => 'error', 'code' => 400, 'message' => 'Invalid Amount'];
            }

            $result = DB::transaction(function () use ($bill, $paidAmount, $trxId, $referenceNo, $trxDateTime, $paymentRequestId, $payload) {
                $lockedBill = Bill::whereKey($bill->id)->lockForUpdate()->first();

                if (! in_array($lockedBill->status, ['pending', 'unpaid', 'waiting_payment'])) {
                    return ['status' => 'error', 'code' => 409, 'message' => 'Paid Bill'];
                }

                $currentPenalty = (float) $lockedBill->penalty_amount
                    + (float) $lockedBill->fixed_fine_amount
                    + (float) $lockedBill->surcharge_amount;

                $this->billingService->settleBill($lockedBill, $currentPenalty, 'BNI');

                $finalRef = $referenceNo ?: ($trxId ?: ('BNI-' . Str::upper(Str::random(12))));
                $externalId = $trxId ?: $paymentRequestId;

                Payment::create([
                    'bill_id' => $lockedBill->id,
                    'tax_object_id' => $lockedBill->tax_object_id,
                    'taxpayer_id' => $lockedBill->taxpayer_id,
                    'transaction_id' => 'BNI-' . ($externalId ?: Str::uuid()->toString()),
                    'reference_number' => $finalRef,
                    'receipt_number' => 'NTPD-BNI-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                    'payment_method' => 'va',
                    'channel' => 'BNI_SNAP',
                    'amount' => $paidAmount,
                    'tendered_amount' => $paidAmount,
                    'change_amount' => 0,
                    'status' => 'success',
                    'billing_period' => $lockedBill->period ?? now()->format('Y-m'),
                    'paid_at' => $trxDateTime,
                    'raw_callback_data' => json_encode($payload),
                ]);

                return ['status' => 'success', 'bill_id' => $lockedBill->id, 'bill_number' => $lockedBill->bill_number];
            });

            Log::info('BNI Payment processed', [
                'bill' => $result['bill_number'] ?? $customerNo,
                'amount' => $paidAmount,
                'trxId' => $trxId,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('BNI Payment process error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['status' => 'error', 'code' => 500, 'message' => $e->getMessage()];
        }
    }

    private function expectedAmount(Bill $bill): float
    {
        return (float) $bill->amount
            + (float) $bill->admin_fee
            + (float) $bill->penalty_amount
            + (float) $bill->fixed_fine_amount
            + (float) $bill->surcharge_amount;
    }
}
