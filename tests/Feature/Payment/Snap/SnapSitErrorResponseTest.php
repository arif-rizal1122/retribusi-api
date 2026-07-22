<?php

namespace Tests\Feature\Payment\Snap;

class SnapSitErrorResponseTest extends SnapFeatureTestCase
{
    public function test_inquiry_rejects_invalid_access_token_with_inquiry_service_code(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body, [
            'Authorization' => 'Bearer invalid-token',
        ]))->assertUnauthorized()
            ->assertJsonPath('responseCode', '4012401')
            ->assertJsonPath('responseMessage', 'Access Token Invalid');
    }

    public function test_payment_rejects_invalid_access_token_with_payment_service_code(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->paymentBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/payment';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body, [
            'Authorization' => 'Bearer invalid-token',
        ]))->assertUnauthorized()
            ->assertJsonPath('responseCode', '4012501')
            ->assertJsonPath('responseMessage', 'Access Token Invalid');
    }

    public function test_inquiry_rejects_missing_mandatory_body_field(): void
    {
        $bill = $this->createOpenBill();
        $body = [
            'partnerServiceId' => '777',
            'customerNo' => $bill->bill_number,
        ];
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertBadRequest()
            ->assertJsonPath('responseCode', '4002402')
            ->assertJsonPath('responseMessage', 'Invalid Mandatory Field {virtualAccountNo}');
    }

    public function test_payment_rejects_invalid_paid_amount_format(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->paymentBody($bill, $vaNumber);
        $body['paidAmount']['currency'] = 'USD';
        $path = '/api/snap/v1.0/transfer-va/payment';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertBadRequest()
            ->assertJsonPath('responseCode', '4002501')
            ->assertJsonPath('responseMessage', 'Invalid Field Format {paidAmount.currency}');
    }

    public function test_payment_rejects_missing_mandatory_paid_amount(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/payment';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertBadRequest()
            ->assertJsonPath('responseCode', '4002502')
            ->assertJsonPath('responseMessage', 'Invalid Mandatory Field {paidAmount}');
    }

    public function test_payment_rejects_missing_mandatory_header_with_payment_service_code(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->paymentBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/payment';
        $headers = $this->transactionHeaders($path, $body);
        unset($headers['X-EXTERNAL-ID']);

        $this->postJson($path, $body, $headers)
            ->assertBadRequest()
            ->assertJsonPath('responseCode', '4002502')
            ->assertJsonPath('responseMessage', 'Invalid Mandatory Field {X-EXTERNAL-ID}');
    }

    public function test_inquiry_returns_sit_expired_bill_response(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777'.$bill->bill_number;
        $paymentRequest = $this->createPaymentRequest($bill, $vaNumber);
        $paymentRequest->update(['expires_at' => now()->subMinute()]);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertNotFound()
            ->assertJsonPath('responseCode', '4042419')
            ->assertJsonPath('responseMessage', 'Bill expired');
    }

    public function test_inquiry_returns_sit_not_found_response(): void
    {
        $body = [
            'partnerServiceId' => '777',
            'customerNo' => 'UNKNOWN-BILL',
            'virtualAccountNo' => '777UNKNOWN-BILL',
        ];
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertNotFound()
            ->assertJsonPath('responseCode', '4042412')
            ->assertJsonPath('responseMessage', 'Bill not found');
    }

    public function test_payment_returns_sit_not_found_response(): void
    {
        $body = [
            'partnerServiceId' => '777',
            'customerNo' => 'UNKNOWN-BILL',
            'virtualAccountNo' => '777UNKNOWN-BILL',
            'paidAmount' => [
                'value' => '100000.00',
                'currency' => 'IDR',
            ],
            'referenceNo' => 'BRI-REF-NOT-FOUND',
        ];
        $path = '/api/snap/v1.0/transfer-va/payment';

        $this->postJson($path, $body, $this->transactionHeaders($path, $body))
            ->assertNotFound()
            ->assertJsonPath('responseCode', '4042512')
            ->assertJsonPath('responseMessage', 'Bill not found');
    }
}
