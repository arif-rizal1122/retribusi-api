<?php

namespace Tests\Feature\Payment;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitizenPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    private Taxpayer $taxpayer;

    private TaxObject $taxObject;

    private Opd $opd;

    private RetributionType $retributionType;

    private RetributionClassification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'snap.briva.enabled' => true,
            'snap.partners.BRI.partner_id' => 'BRI-PARTNER-LOCAL',
            'snap.partners.BRI.client_key' => 'BRI-CLIENT-LOCAL',
            'snap.partners.BRI.public_key' => '-----BEGIN PUBLIC KEY----- test -----END PUBLIC KEY-----',
            'snap.briva.va_prefix' => '777',
            'snap.briva.va_length' => 18,
            'snap.briva.payment_request_expiry_minutes' => 1440,
        ]);

        $this->opd = Opd::factory()->create();
        $this->retributionType = RetributionType::factory()->create(['opd_id' => $this->opd->id]);
        $this->classification = RetributionClassification::factory()->create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->retributionType->id,
        ]);
        $this->taxpayer = Taxpayer::factory()->create(['opd_id' => $this->opd->id]);
        $this->taxObject = TaxObject::factory()->create([
            'opd_id' => $this->opd->id,
            'taxpayer_id' => $this->taxpayer->id,
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
        ]);
    }

    public function test_citizen_can_create_reuse_refresh_and_cancel_a_multi_bill_payment_request(): void
    {
        $firstBill = $this->createBill(['amount' => 100000]);
        $secondBill = $this->createBill(['amount' => 25000]);
        Sanctum::actingAs($this->taxpayer);

        $createResponse = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$secondBill->id, $firstBill->id],
            'method' => 'bri_va',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.method', 'bri_va')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_amount', 125000)
            ->assertJsonCount(2, 'data.bill_ids')
            ->assertJsonCount(0, 'data.receipts')
            ->assertJsonPath('data.receipt_number', null)
            ->assertJsonPath('data.receipt_url', null);

        $paymentRequestId = $createResponse->json('data.id');
        $paymentRequest = PaymentRequest::findOrFail($paymentRequestId);

        $this->assertSame('777'.str_pad((string) $paymentRequest->id, 15, '0', STR_PAD_LEFT), $paymentRequest->va_number);
        $this->assertDatabaseCount('payment_request_items', 2);

        $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$firstBill->id, $secondBill->id],
            'method' => 'bri_va',
        ])->assertOk()->assertJsonPath('data.id', (string) $paymentRequest->id);

        $this->assertDatabaseCount('payment_requests', 1);

        $this->postJson("/api/citizen/payment-requests/{$paymentRequest->id}/refresh")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.can_refresh', true);

        $this->postJson("/api/citizen/payment-requests/{$paymentRequest->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.can_refresh', false)
            ->assertJsonPath('data.can_cancel', false);
    }

    public function test_citizen_cannot_read_another_taxpayers_payment_request(): void
    {
        $bill = $this->createBill();
        Sanctum::actingAs($this->taxpayer);

        $paymentRequestId = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs(Taxpayer::factory()->create(['opd_id' => $this->opd->id]));

        $this->getJson("/api/citizen/payment-requests/{$paymentRequestId}")
            ->assertNotFound();
    }

    public function test_expired_payment_request_is_marked_expired_and_cannot_be_cancelled(): void
    {
        $bill = $this->createBill();
        Sanctum::actingAs($this->taxpayer);

        $paymentRequestId = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertCreated()->json('data.id');

        PaymentRequest::whereKey($paymentRequestId)->update(['expires_at' => now()->subMinute()]);

        $this->postJson("/api/citizen/payment-requests/{$paymentRequestId}/refresh")
            ->assertOk()
            ->assertJsonPath('data.status', 'expired')
            ->assertJsonPath('data.can_refresh', false)
            ->assertJsonPath('data.can_cancel', false);

        $this->postJson("/api/citizen/payment-requests/{$paymentRequestId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'expired');
    }

    public function test_pending_manual_claim_blocks_briva_payment_request(): void
    {
        $bill = $this->createBill();
        Payment::create([
            'bill_id' => $bill->id,
            'taxpayer_id' => $bill->taxpayer_id,
            'tax_object_id' => $bill->tax_object_id,
            'transaction_id' => 'PAY-MANUAL-PENDING',
            'payment_method' => 'transfer',
            'amount' => $bill->total_amount,
            'status' => 'pending',
            'billing_period' => $bill->period,
            'proof_url' => 'https://example.test/proof.jpg',
        ]);
        Sanctum::actingAs($this->taxpayer);

        $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bill_ids');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_briva_payment_request_is_rejected_when_runtime_gate_is_disabled(): void
    {
        config(['snap.briva.enabled' => false]);
        $bill = $this->createBill();
        Sanctum::actingAs($this->taxpayer);

        $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Channel BRIVA belum tersedia. Gunakan metode pembayaran lain.');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    public function test_active_briva_request_blocks_manual_claim_for_same_bill(): void
    {
        $bill = $this->createBill();
        Sanctum::actingAs($this->taxpayer);

        $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertCreated();

        $this->postJson('/api/citizen/payments', [
            'bill_ids' => [$bill->id],
            'payment_method' => 'transfer',
            'proof_url' => 'https://example.test/proof.jpg',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bill_ids');

        $this->assertDatabaseCount('payments', 0);
    }

    private function createBill(array $overrides = []): Bill
    {
        return Bill::factory()->create(array_merge([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $this->taxObject->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
            'amount' => 100000,
            'admin_fee' => 0,
            'penalty_amount' => 0,
            'fixed_fine_amount' => 0,
            'surcharge_amount' => 0,
            'waived_penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-07',
            'due_date' => now()->addWeek(),
        ], $overrides));
    }
}
