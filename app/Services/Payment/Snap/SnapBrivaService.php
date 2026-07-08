<?php

namespace App\Services\Payment\Snap;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Services\Payment\Snap\Exceptions\SnapPaymentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SnapBrivaService
{
    public function __construct(private readonly SensitivePaymentLogMasker $masker)
    {
    }

    public function inquiry(Request $request): array
    {
        [$bill, $paymentRequest] = $this->resolveOpenBill($request);

        return $this->virtualAccountData($request, $bill, [
            'inquiryStatus' => '00',
            'inquiryReason' => 'Success',
        ], $paymentRequest);
    }

    public function payment(Request $request): array
    {
        [$bill, $paymentRequest] = $this->resolveOpenBill($request);
        $paidAmount = $this->extractAmount($request);
        $expectedAmount = (float) $bill->total_amount;

        if (!$this->sameAmount($expectedAmount, $paidAmount)) {
            throw new SnapPaymentException('4002401', 'Bad Request. Paid amount does not match bill amount.', 400);
        }

        return DB::transaction(function () use ($request, $bill, $paymentRequest, $paidAmount) {
            $lockedBill = Bill::whereKey($bill->id)->lockForUpdate()->firstOrFail();

            if ($this->isPaid($lockedBill)) {
                throw new SnapPaymentException('4092400', 'Conflict. Bill already paid.', 409);
            }

            $referenceNumber = $this->referenceNumber($request);
            $externalId = (string) $request->header('X-EXTERNAL-ID');

            $lockedBill->update([
                'status' => 'lunas',
                'penalty_at_payment' => (float) $lockedBill->penalty_amount + (float) $lockedBill->fixed_fine_amount + (float) $lockedBill->surcharge_amount,
                'bank_code' => 'BRI',
            ]);

            Payment::create([
                'bill_id' => $lockedBill->id,
                'tax_object_id' => $lockedBill->tax_object_id,
                'taxpayer_id' => $lockedBill->taxpayer_id,
                'transaction_id' => 'SNAP-' . ($externalId !== '' ? $externalId : Str::uuid()->toString()),
                'reference_number' => $referenceNumber,
                'receipt_number' => 'NTPD-SNAP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                'payment_method' => 'va',
                'channel' => 'BRI_SNAP',
                'amount' => $paidAmount,
                'status' => 'success',
                'billing_period' => $lockedBill->period ?? now()->format('Y-m'),
                'paid_at' => now(),
                'raw_callback_data' => $this->masker->mask($request->all()),
            ]);

            if ($paymentRequest) {
                $paymentRequest->update([
                    'provider_reference' => $referenceNumber,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'raw_request_safe' => $this->masker->mask($request->all()),
                ]);
            }

            return $this->virtualAccountData($request, $lockedBill, [
                'paymentFlagStatus' => '00',
                'paymentFlagReason' => 'Success',
            ], $paymentRequest);
        });
    }

    private function resolveOpenBill(Request $request): array
    {
        [$bill, $paymentRequest] = $this->resolveBill($request);

        if (!$bill) {
            throw new SnapPaymentException('4042412', 'Bill not found / Invalid VA.', 404);
        }

        if ($paymentRequest && $paymentRequest->expires_at && $paymentRequest->expires_at->isPast()) {
            throw new SnapPaymentException('4042419', 'Bill expired.', 404);
        }

        if ($this->isPaid($bill)) {
            throw new SnapPaymentException('4092400', 'Conflict. Bill already paid.', 409);
        }

        return [$bill, $paymentRequest];
    }

    private function resolveBill(Request $request): array
    {
        $virtualAccountNo = (string) $request->input('virtualAccountNo', '');
        $customerNo = (string) $request->input('customerNo', '');

        $paymentRequest = null;
        if ($virtualAccountNo !== '') {
            $paymentRequest = PaymentRequest::where('va_number', $virtualAccountNo)->first();
        }

        if (!$paymentRequest && $customerNo !== '') {
            $paymentRequest = PaymentRequest::where('external_id', $customerNo)->first();
        }

        $bill = $paymentRequest?->bill;

        if (!$bill && $customerNo !== '') {
            $bill = Bill::where('bill_number', $customerNo)->first();
        }

        if (!$bill && $virtualAccountNo !== '') {
            $bill = Bill::where('bill_number', $virtualAccountNo)->first();
        }

        return [$bill, $paymentRequest];
    }

    private function virtualAccountData(Request $request, Bill $bill, array $status, ?PaymentRequest $paymentRequest = null): array
    {
        $partnerServiceId = (string) $request->input('partnerServiceId', '');
        $customerNo = (string) $request->input('customerNo', $bill->bill_number);
        $virtualAccountNo = (string) $request->input('virtualAccountNo', $paymentRequest?->va_number ?? ($partnerServiceId . $customerNo));
        $totalAmount = $this->money((float) $bill->total_amount);

        return array_merge([
            'partnerServiceId' => $partnerServiceId,
            'customerNo' => $customerNo,
            'virtualAccountNo' => $virtualAccountNo,
            'virtualAccountName' => $bill->taxpayer->name ?? 'Wajib Pajak',
            'virtualAccountEmail' => $bill->taxpayer->email ?? '',
            'virtualAccountPhone' => $bill->taxpayer->phone ?? '',
            'totalAmount' => [
                'value' => $totalAmount,
                'currency' => 'IDR',
            ],
            'billDetails' => [
                [
                    'billNo' => $bill->bill_number,
                    'billDescription' => 'Tagihan Retribusi',
                    'billAmount' => [
                        'value' => $totalAmount,
                        'currency' => 'IDR',
                    ],
                ],
            ],
        ], $status);
    }

    private function extractAmount(Request $request): float
    {
        foreach (['paidAmount', 'totalAmount', 'amount'] as $field) {
            $value = $request->input($field);

            if (is_array($value) && isset($value['value'])) {
                return (float) $value['value'];
            }
        }

        foreach (['amount_paid', 'paid_amount'] as $field) {
            if ($request->filled($field)) {
                return (float) $request->input($field);
            }
        }

        throw new SnapPaymentException('4002402', 'Bad Request. Missing paid amount.', 400);
    }

    private function referenceNumber(Request $request): string
    {
        foreach (['referenceNo', 'partnerReferenceNo', 'paymentRequestId'] as $field) {
            if ($request->filled($field)) {
                return (string) $request->input($field);
            }
        }

        return (string) $request->header('X-EXTERNAL-ID');
    }

    private function sameAmount(float $expected, float $actual): bool
    {
        return abs(round($expected, 2) - round($actual, 2)) < 0.01;
    }

    private function isPaid(Bill $bill): bool
    {
        return in_array($bill->getRawOriginal('status'), ['lunas', 'paid'], true)
            || $bill->payments()->where('status', 'success')->exists();
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
