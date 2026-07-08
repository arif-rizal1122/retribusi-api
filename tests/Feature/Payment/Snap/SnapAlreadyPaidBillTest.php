<?php

namespace Tests\Feature\Payment\Snap;

class SnapAlreadyPaidBillTest extends SnapFeatureTestCase
{
    public function test_inquiry_for_paid_bill_is_rejected(): void
    {
        $bill = $this->createOpenBill(['status' => 'lunas']);
        $vaNumber = '777' . $bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body));

        $response->assertStatus(409)
            ->assertJsonPath('responseCode', '4092400');
    }
}
