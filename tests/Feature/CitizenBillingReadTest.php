<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitizenBillingReadTest extends TestCase
{
    use RefreshDatabase;

    private Opd $opd;
    private RetributionType $retributionType;
    private RetributionClassification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::factory()->create();
        $this->retributionType = RetributionType::factory()->create([
            'opd_id' => $this->opd->id,
        ]);
        $this->classification = RetributionClassification::factory()->create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->retributionType->id,
        ]);
    }

    public function test_guest_cannot_read_citizen_bills_by_supplying_a_nik(): void
    {
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $this->createBill($taxpayer);

        $this->getJson('/api/citizen/bills?nik=' . $taxpayer->nik)
            ->assertUnauthorized();
    }

    public function test_citizen_bill_list_is_owner_scoped_and_paginated(): void
    {
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $otherTaxpayer = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $ownBills = collect([
            $this->createBill($taxpayer),
            $this->createBill($taxpayer),
            $this->createBill($taxpayer),
        ]);
        $otherBill = $this->createBill($otherTaxpayer);

        Sanctum::actingAs($taxpayer);

        $response = $this->getJson('/api/citizen/bills?per_page=2&nik=' . $otherTaxpayer->nik);

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);

        $returnedIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($returnedIds->every(fn ($id) => $ownBills->pluck('id')->contains($id)));
        $this->assertFalse($returnedIds->contains($otherBill->id));
    }

    public function test_citizen_payment_history_is_owner_scoped_and_hides_internal_payloads(): void
    {
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $otherTaxpayer = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $bill = $this->createBill($taxpayer);
        $otherBill = $this->createBill($otherTaxpayer);

        $payment = $this->createPayment($bill, [
            'reference_number' => 'BRI-REF-001',
            'receipt_number' => 'NTPD-001',
            'raw_callback_data' => ['X-SIGNATURE' => 'must-not-leak'],
        ]);
        $this->createPayment($otherBill, ['transaction_id' => 'PAY-OTHER-001']);

        Sanctum::actingAs($taxpayer);

        $response = $this->getJson('/api/citizen/payments/history');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $payment->id)
            ->assertJsonPath('data.0.reference_number', 'BRI-REF-001')
            ->assertJsonPath('data.0.receipt_number', 'NTPD-001')
            ->assertJsonPath('data.0.retribution_type.id', $this->retributionType->id)
            ->assertJsonPath('data.0.tax_object.id', $bill->tax_object_id);

        $this->assertArrayNotHasKey('raw_callback_data', $response->json('data.0'));
        $this->assertArrayNotHasKey('approved_by', $response->json('data.0'));
    }

    public function test_internal_user_cannot_use_citizen_billing_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'super_admin']));

        $this->getJson('/api/citizen/bills')->assertForbidden();
        $this->getJson('/api/citizen/payments/history')->assertForbidden();
    }

    private function createBill(Taxpayer $taxpayer): Bill
    {
        $taxObject = TaxObject::factory()->create([
            'opd_id' => $this->opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
        ]);

        return Bill::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
            'period' => now()->format('Y-m'),
        ]);
    }

    private function createPayment(Bill $bill, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'bill_id' => $bill->id,
            'taxpayer_id' => $bill->taxpayer_id,
            'tax_object_id' => $bill->tax_object_id,
            'transaction_id' => 'PAY-' . $bill->id,
            'reference_number' => null,
            'receipt_number' => null,
            'payment_method' => 'transfer',
            'channel' => 'MOBILE_CLAIM',
            'amount' => $bill->amount,
            'status' => 'pending',
            'billing_period' => $bill->period,
            'paid_at' => now(),
            'proof_url' => 'https://example.test/proof.jpg',
        ], $overrides));
    }
}
