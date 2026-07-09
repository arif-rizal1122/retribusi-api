<?php

namespace Tests\Feature\Payment\Core;

class CitizenPaymentClaimStillWorksTest extends CorePaymentTestCase
{
    public function test_citizen_can_still_submit_payment_claim_with_proof(): void
    {
        $bill = $this->createCoreBill(['amount' => 90000]);

        $response = $this->actingAs($bill->taxpayer)->postJson('/api/citizen/payments', [
            'tax_object_id' => $bill->tax_object_id,
            'billing_period' => $bill->period,
            'payment_method' => 'transfer',
            'amount' => 90000,
            'proof_url' => 'https://example.test/proof.jpg',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->id,
            'taxpayer_id' => $bill->taxpayer_id,
            'status' => 'pending',
        ]);
    }
}
