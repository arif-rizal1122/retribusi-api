<?php

namespace Tests\Feature\Payment\Snap;

class SnapExpiredTimestampTest extends SnapFeatureTestCase
{
    public function test_transaction_with_expired_timestamp_is_rejected(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777' . $bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';

        $response = $this->postJson($path, $body, $this->transactionHeaders($path, $body, [
            'X-TIMESTAMP' => now()->subMinutes(10)->toIso8601String(),
        ]));

        $response->assertStatus(401)
            ->assertJsonPath('responseCode', '4012400');
    }
}
