<?php

namespace Tests\Feature\Payment\Snap;

class SnapDuplicateExternalIdTest extends SnapFeatureTestCase
{
    public function test_completed_duplicate_external_id_returns_idempotent_response(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777' . $bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $body = $this->inquiryBody($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';
        $headers = $this->transactionHeaders($path, $body, [
            'X-EXTERNAL-ID' => 'EXT-IDEMPOTENT-001',
        ]);

        $first = $this->postJson($path, $body, $headers);
        $second = $this->postJson($path, $body, $headers);

        $first->assertOk();
        $second->assertOk()
            ->assertJsonPath('responseCode', '2002400')
            ->assertJsonPath('virtualAccountData.virtualAccountNo', $vaNumber);

        $this->assertDatabaseCount('snap_idempotency_keys', 1);
    }

    public function test_duplicate_external_id_with_different_payload_is_rejected(): void
    {
        $bill = $this->createOpenBill();
        $vaNumber = '777' . $bill->bill_number;
        $this->createPaymentRequest($bill, $vaNumber);
        $path = '/api/snap/v1.0/transfer-va/inquiry';
        $externalId = 'EXT-IDEMPOTENT-DIFFERENT-001';

        $firstBody = $this->inquiryBody($bill, $vaNumber);
        $firstHeaders = $this->transactionHeaders($path, $firstBody, [
            'X-EXTERNAL-ID' => $externalId,
        ]);

        $secondBody = array_merge($firstBody, ['customerNo' => 'DIFFERENT-BILL']);
        $secondHeaders = $this->transactionHeaders($path, $secondBody, [
            'X-EXTERNAL-ID' => $externalId,
        ]);

        $this->postJson($path, $firstBody, $firstHeaders)->assertOk();

        $this->postJson($path, $secondBody, $secondHeaders)
            ->assertStatus(409)
            ->assertJsonPath('responseCode', '4092400');
    }
}
