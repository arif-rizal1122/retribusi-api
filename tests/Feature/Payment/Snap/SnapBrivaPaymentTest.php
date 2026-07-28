<?php

namespace Tests\Feature\Payment\Snap;

class SnapBrivaPaymentTest extends SnapFeatureTestCase
{
    public function test_bank_can_confirm_exact_amount_briva_payment(): void
    {
        $bill = $this->createOpenBill(['amount' => 125000]);
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->paymentBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/payment';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertOk()
            ->assertJsonPath('responseCode', '2002500')
            ->assertJsonPath('virtualAccountData.paymentFlagStatus', '00')
            ->assertJsonPath('virtualAccountData.paymentRequestId', $body['paymentRequestId'])
            ->assertJsonPath('virtualAccountData.paidAmount.value', '125000.00')
            ->assertJsonPath('virtualAccountData.paymentFlagReason.english', 'Success')
            ->assertJsonPath('virtualAccountData.paymentFlagReason.indonesia', 'Sukses');

        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'status' => 'lunas',
            'bank_code' => 'BRI',
        ]);
        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->id,
            'payment_method' => 'va',
            'channel' => 'BRI_SNAP',
            'amount' => 125000,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('payment_requests', [
            'bill_id' => $bill->id,
            'status' => 'paid',
        ]);
    }

    public function test_cancelled_briva_payment_request_cannot_be_paid(): void
    {
        $bill = $this->createOpenBill(['amount' => 125000]);
        $vaNumber = '777'.$bill->bill_number;
        $paymentRequest = $this->createPaymentRequest($bill, $vaNumber);
        $paymentRequest->update(['status' => 'cancelled']);
        $body = $this->paymentBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/payment';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertNotFound()
            ->assertJsonPath('responseCode', '4042512')
            ->assertJsonPath('responseMessage', 'Invalid Bill/Virtual Account [Reason]');

        $this->assertDatabaseMissing('payments', [
            'bill_id' => $bill->id,
            'status' => 'success',
        ]);
    }
}
