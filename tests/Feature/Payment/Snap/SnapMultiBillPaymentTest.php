<?php

namespace Tests\Feature\Payment\Snap;

use App\Models\Bill;
use App\Models\PaymentRequestItem;

class SnapMultiBillPaymentTest extends SnapFeatureTestCase
{
    public function test_bank_callback_settles_every_bill_in_a_multi_bill_payment_request(): void
    {
        $firstBill = $this->createOpenBill(['amount' => 125000]);
        $secondBill = Bill::factory()->create([
            'bill_number' => 'SKRD-SNAP-MULTI-SECOND',
            'taxpayer_id' => $firstBill->taxpayer_id,
            'tax_object_id' => $firstBill->tax_object_id,
            'opd_id' => $firstBill->opd_id,
            'retribution_type_id' => $firstBill->retribution_type_id,
            'retribution_classification_id' => $firstBill->retribution_classification_id,
            'amount' => 75000,
            'admin_fee' => 0,
            'penalty_amount' => 0,
            'fixed_fine_amount' => 0,
            'surcharge_amount' => 0,
            'waived_penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-07',
            'due_date' => now()->addMonth(),
        ]);
        $vaNumber = '777' . $firstBill->bill_number;
        $paymentRequest = $this->createPaymentRequest($firstBill, $vaNumber);
        $paymentRequest->update(['amount_snapshot' => 200000, 'admin_fee_snapshot' => 0, 'penalty_snapshot' => 0]);

        PaymentRequestItem::create([
            'payment_request_id' => $paymentRequest->id,
            'bill_id' => $firstBill->id,
            'amount_snapshot' => 125000,
        ]);
        PaymentRequestItem::create([
            'payment_request_id' => $paymentRequest->id,
            'bill_id' => $secondBill->id,
            'amount_snapshot' => 75000,
        ]);

        $body = $this->paymentBody($firstBill, $vaNumber, '200000.00');
        $path = '/api/snap/v1.0/transfer-va/payment';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertOk()
            ->assertJsonPath('responseCode', '2002500')
            ->assertJsonPath('virtualAccountData.totalAmount.value', '200000.00')
            ->assertJsonCount(2, 'virtualAccountData.billDetails');

        foreach ([$firstBill, $secondBill] as $bill) {
            $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'lunas', 'bank_code' => 'BRI']);
            $this->assertDatabaseHas('payments', [
                'bill_id' => $bill->id,
                'payment_method' => 'va',
                'channel' => 'BRI_SNAP',
                'status' => 'success',
            ]);
            $this->assertDatabaseHas('payment_request_items', [
                'payment_request_id' => $paymentRequest->id,
                'bill_id' => $bill->id,
                'status' => 'paid',
            ]);
        }

        $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'paid']);
    }
}
