<?php

namespace Tests\Feature\Payment\Snap;

class SnapBrivaInquiryTest extends SnapFeatureTestCase
{
    public function test_bank_can_inquiry_open_briva_bill(): void
    {
        $bill = $this->createOpenBill([
            'amount' => 150000,
            'admin_fee' => 2500,
        ]);
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertOk()
            ->assertJsonPath('responseCode', '2002400')
            ->assertJsonPath('virtualAccountData.virtualAccountNo', $vaNumber)
            ->assertJsonPath('virtualAccountData.totalAmount.value', '152500.00')
            ->assertJsonPath('virtualAccountData.inquiryStatus', '00');
    }

    public function test_cancelled_briva_payment_request_cannot_be_inquired(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $paymentRequest = $this->createPaymentRequest($bill, $vaNumber);
        $paymentRequest->update(['status' => 'cancelled']);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertNotFound()
            ->assertJsonPath('responseCode', '4042412')
            ->assertJsonPath('responseMessage', 'Bill not found');
    }

    public function test_raw_bill_number_without_active_payment_request_is_not_a_briva_bill(): void
    {
        $bill = $this->createOpenBill();
        $body = $this->inquiryBody($bill, $bill->bill_number);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertNotFound()
            ->assertJsonPath('responseCode', '4042412')
            ->assertJsonPath('responseMessage', 'Bill not found');
    }
}
