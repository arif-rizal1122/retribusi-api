<?php

namespace Tests\Feature\Payment;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestItem;
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
            'snap.partners.BRI.signature_algorithm' => 'rsa_sha256',
            'snap.partners.BRI.signature_secret' => '',
            'snap.partners.BRI.public_key' => $this->publicKey(),
            'snap.briva.partner_service_id' => '',
            'snap.briva.va_prefix' => '777',
            'snap.briva.va_length' => 18,
            'snap.briva.customer_no_length' => 15,
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

    public function test_citizen_can_create_payment_request_for_legacy_uppercase_unpaid_bill(): void
    {
        $bill = $this->createBill(['status' => 'UNPAID']);
        Sanctum::actingAs($this->taxpayer);

        $response = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.bills.0.status', 'pending_verification')
            ->assertJsonPath('data.bills.0.can_pay', false);
        $this->assertSame('UNPAID', $bill->fresh()->getRawOriginal('status'));
    }

    public function test_payment_request_bill_details_expose_canonical_bill_statuses(): void
    {
        Sanctum::actingAs($this->taxpayer);

        $pendingResponse = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$this->createBill()->id],
            'method' => 'bri_va',
        ])->assertCreated();

        $pendingResponse
            ->assertJsonPath('data.bills.0.status', 'pending_verification')
            ->assertJsonPath('data.bills.0.status_label', 'Menunggu pembayaran BRIVA')
            ->assertJsonPath('data.bills.0.can_pay', false);

        $cancelledBill = $this->createBill(['due_date' => now()->addWeek()]);
        $cancelledRequest = $this->attachPaymentRequest($cancelledBill, 'cancelled');

        $this->getJson("/api/citizen/payment-requests/{$cancelledRequest->id}")
            ->assertOk()
            ->assertJsonPath('data.bills.0.status', 'pending')
            ->assertJsonPath('data.bills.0.status_label', 'Menunggu pembayaran')
            ->assertJsonPath('data.bills.0.can_pay', true);

        $paidBill = $this->createBill(['status' => 'lunas']);
        $paidRequest = $this->attachPaymentRequest($paidBill, 'paid');

        $this->getJson("/api/citizen/payment-requests/{$paidRequest->id}")
            ->assertOk()
            ->assertJsonPath('data.bills.0.status', 'paid')
            ->assertJsonPath('data.bills.0.status_label', 'Lunas')
            ->assertJsonPath('data.bills.0.can_pay', false);

        $overdueBill = $this->createBill(['due_date' => now()->subDay()]);
        $overdueRequest = $this->attachPaymentRequest($overdueBill, 'cancelled');

        $this->getJson("/api/citizen/payment-requests/{$overdueRequest->id}")
            ->assertOk()
            ->assertJsonPath('data.bills.0.status', 'overdue')
            ->assertJsonPath('data.bills.0.status_label', 'Jatuh tempo')
            ->assertJsonPath('data.bills.0.can_pay', true);
    }

    public function test_payment_request_status_aliases_are_canonical_at_the_citizen_boundary(): void
    {
        Sanctum::actingAs($this->taxpayer);

        $cases = [
            'success' => ['paid', 'Pembayaran diterima.'],
            'processing' => ['processing_receipt', 'Pembayaran sedang diproses.'],
            'void' => ['cancelled', 'Payment request dibatalkan.'],
            'unrecognized' => ['unknown', 'Status pembayaran belum dapat dipastikan.'],
        ];

        foreach ($cases as $storedStatus => [$expectedStatus, $expectedLabel]) {
            $bill = $this->createBill();
            $paymentRequest = $this->attachPaymentRequest($bill, $storedStatus);

            $this->getJson("/api/citizen/payment-requests/{$paymentRequest->id}")
                ->assertOk()
                ->assertJsonPath('data.status', $expectedStatus)
                ->assertJsonPath('data.status_label', $expectedLabel);
        }
    }

    public function test_citizen_briva_va_uses_partner_service_id_and_numeric_customer_no(): void
    {
        config([
            'snap.briva.partner_service_id' => '00077777',
            'snap.briva.va_prefix' => '',
            'snap.briva.customer_no_length' => 20,
            'snap.briva.va_length' => 28,
        ]);

        $bill = $this->createBill();
        Sanctum::actingAs($this->taxpayer);

        $response = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertCreated();

        $paymentRequest = PaymentRequest::findOrFail($response->json('data.id'));
        $expectedVa = '00077777'.str_pad((string) $paymentRequest->id, 20, '0', STR_PAD_LEFT);

        $this->assertSame($expectedVa, $paymentRequest->va_number);
        $this->assertSame(28, strlen($paymentRequest->va_number));
        $this->assertMatchesRegularExpression('/^\d{28}$/', $paymentRequest->va_number);
    }

    public function test_citizen_briva_va_can_use_bri_confirmed_prefix_for_local_smoke(): void
    {
        config([
            'snap.partners.BRI.signature_algorithm' => 'rsa_sha256',
            'snap.partners.BRI.signature_secret' => '',
            'snap.partners.BRI.public_key' => $this->publicKey(),
            'snap.briva.partner_service_id' => '',
            'snap.briva.va_prefix' => '19960',
            'snap.briva.va_length' => 18,
        ]);

        $bill = $this->createBill();
        Sanctum::actingAs($this->taxpayer);

        $response = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->assertCreated();

        $paymentRequest = PaymentRequest::findOrFail($response->json('data.id'));
        $expectedVa = '19960'.str_pad((string) $paymentRequest->id, 13, '0', STR_PAD_LEFT);

        $this->assertSame($expectedVa, $paymentRequest->va_number);
        $this->assertSame($paymentRequest->va_number, $response->json('data.va_number'));
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

    private function attachPaymentRequest(Bill $bill, string $status): PaymentRequest
    {
        $paymentRequest = PaymentRequest::create([
            'bill_id' => $bill->id,
            'tax_object_id' => $bill->tax_object_id,
            'taxpayer_id' => $this->taxpayer->id,
            'payment_channel' => 'BRI',
            'method' => 'VA',
            'amount_snapshot' => $bill->amount,
            'admin_fee_snapshot' => $bill->admin_fee,
            'penalty_snapshot' => $bill->penalty_amount,
            'expires_at' => now()->addDay(),
            'external_id' => 'TEST-PR-'.$bill->id,
            'status' => $status,
        ]);

        PaymentRequestItem::create([
            'payment_request_id' => $paymentRequest->id,
            'bill_id' => $bill->id,
            'amount_snapshot' => $bill->total_amount,
            'status' => $status === 'paid' ? 'paid' : 'pending',
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        return $paymentRequest;
    }

    public function test_citizen_can_create_bank_sultra_va_payment_request(): void
    {
        config([
            'snap.bank_sultra_va.enabled' => true,
            'snap.bank_sultra_va.contract_confirmed' => true,
        ]);

        Sanctum::actingAs($this->taxpayer, ['*']);
        $bill = $this->createBill(['amount' => 50000]);

        $createResponse = $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'sultra_va',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.method', 'sultra_va')
            ->assertJsonPath('data.provider', 'Bank Sultra')
            ->assertJsonPath('data.status', 'pending');

        $vaNumber = $createResponse->json('data.va_number');
        $this->assertNotEmpty($vaNumber);
        $this->assertStringStartsWith('00007472', $vaNumber);
        $this->assertEquals(16, strlen($vaNumber));
    }

    public function test_citizen_sultra_va_payment_request_is_rejected_until_contract_is_ready(): void
    {
        Sanctum::actingAs($this->taxpayer, ['*']);
        $bill = $this->createBill(['amount' => 50000]);

        $this->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'sultra_va',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Virtual Account Bank Sultra belum tersedia. Gunakan transfer manual atau metode lain.');

        $this->assertDatabaseCount('payment_requests', 0);
    }

    private function publicKey(): string
    {
        return <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwYlN41PX9hRBKFRzmtsl
V+E2ON+xxbGUrA0wOwFgmrQNFBuQFqQ2Oe5w15Zu0E5mnNl8EHby3cLU1Kue0JiO
K970LMuSyc57x1I+FWOxuBNoi1YxdPjZBdznxk/DzdIPqIVBFHIHJho+ZNCPG+Ch
spWMD5lo4T+sHhnXxzgLKkBeQcVI2fmCUCp6iTaIwgNWl6L7mG9qo60z/1ae6b+1
GdW6EnuwEgRaXPZDW/zwxV0xAU8XoIB/1DNu4qE5QJrB4u5NVTNvAHjtmo8R7cwp
v4B8OlqYl0c3Ki6pGLMZUqZ/7ZLz3aV+3jsihMVACFiPqtBGUCk2Ie4PuuRxw8/7
5QIDAQAB
-----END PUBLIC KEY-----
PEM;
    }
}
