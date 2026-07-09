<?php

namespace Tests\Feature\Payment\Snap;

class SnapPaymentAmountMismatchTest extends SnapFeatureTestCase
{
    public function test_payment_with_mismatched_amount_is_rejected(): void
    {
        $bill = $this->createOpenBill(['amount' => 100000]);
        $vaNumber = '777' . $bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->paymentBody($bill, $vaNumber, '99999.00');
        $path = '/api/snap/v1.0/transfer-va/payment';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertStatus(400)
            ->assertJsonPath('responseCode', '4002401');

        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('payments', [
            'bill_id' => $bill->id,
            'status' => 'success',
        ]);
    }
}
