<?php

namespace Tests\Feature\Payment\Core;

use App\Models\Bill;
use App\Models\Payment;

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

    public function test_citizen_can_submit_multiple_bill_claims_atomically_with_backend_amounts(): void
    {
        $firstBill = $this->createCoreBill([
            'amount' => 90000,
            'admin_fee' => 2500,
            'penalty_amount' => 5000,
        ]);
        $secondBill = Bill::factory()->create([
            'bill_number' => 'SKRD-CORE-SECOND',
            'taxpayer_id' => $firstBill->taxpayer_id,
            'tax_object_id' => $firstBill->tax_object_id,
            'opd_id' => $firstBill->opd_id,
            'retribution_type_id' => $firstBill->retribution_type_id,
            'retribution_classification_id' => $firstBill->retribution_classification_id,
            'amount' => 40000,
            'admin_fee' => 1000,
            'status' => 'unpaid',
            'period' => '2026-08',
            'due_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($firstBill->taxpayer)->postJson('/api/citizen/payments', [
            'bill_ids' => [$firstBill->id, $secondBill->id],
            'payment_method' => 'transfer',
            'proof_url' => 'https://example.test/proof.jpg',
        ]);

        $response->assertCreated()->assertJsonCount(2, 'data');
        $this->assertDatabaseHas('payments', [
            'bill_id' => $firstBill->id,
            'amount' => 97500,
            'status' => 'pending',
            'channel' => 'MOBILE_CLAIM',
        ]);
        $this->assertDatabaseHas('payments', [
            'bill_id' => $secondBill->id,
            'amount' => 41000,
            'status' => 'pending',
            'channel' => 'MOBILE_CLAIM',
        ]);
    }

    public function test_multi_bill_claim_creates_nothing_when_one_bill_already_has_a_pending_claim(): void
    {
        $firstBill = $this->createCoreBill();
        $secondBill = Bill::factory()->create([
            'bill_number' => 'SKRD-CORE-CONFLICT',
            'taxpayer_id' => $firstBill->taxpayer_id,
            'tax_object_id' => $firstBill->tax_object_id,
            'opd_id' => $firstBill->opd_id,
            'retribution_type_id' => $firstBill->retribution_type_id,
            'retribution_classification_id' => $firstBill->retribution_classification_id,
            'amount' => 40000,
            'status' => 'pending',
            'period' => '2026-08',
            'due_date' => now()->addMonth(),
        ]);
        Payment::create([
            'bill_id' => $secondBill->id,
            'taxpayer_id' => $secondBill->taxpayer_id,
            'tax_object_id' => $secondBill->tax_object_id,
            'transaction_id' => 'PAY-EXISTING',
            'payment_method' => 'transfer',
            'amount' => $secondBill->total_amount,
            'status' => 'pending',
            'billing_period' => $secondBill->period,
            'proof_url' => 'https://example.test/existing-proof.jpg',
        ]);

        $response = $this->actingAs($firstBill->taxpayer)->postJson('/api/citizen/payments', [
            'bill_ids' => [$firstBill->id, $secondBill->id],
            'payment_method' => 'transfer',
            'proof_url' => 'https://example.test/new-proof.jpg',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('bill_ids');
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseMissing('payments', ['bill_id' => $firstBill->id]);
    }
}
