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
        $vaNumber = '777' . $bill->bill_number;
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
}
