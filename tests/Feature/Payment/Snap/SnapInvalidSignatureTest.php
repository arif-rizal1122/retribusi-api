<?php

namespace Tests\Feature\Payment\Snap;

class SnapInvalidSignatureTest extends SnapFeatureTestCase
{
    public function test_transaction_with_invalid_signature_is_rejected(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body, [
            'X-SIGNATURE' => 'invalid-signature',
        ]));

        $response->assertStatus(401)
            ->assertJsonPath('responseCode', '4012400')
            ->assertJsonPath('responseMessage', 'Unauthorized. Signature');
    }

    public function test_payment_with_invalid_signature_uses_payment_service_code(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->paymentBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/payment';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body, [
            'X-SIGNATURE' => 'invalid-signature',
        ]));

        $response->assertUnauthorized()
            ->assertJsonPath('responseCode', '4012500')
            ->assertJsonPath('responseMessage', 'Unauthorized. Signature');
    }
}
