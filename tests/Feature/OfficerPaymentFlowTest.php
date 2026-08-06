<?php

namespace Tests\Feature;

use App\Models\AftRegistration;
use App\Models\AftTransaction;
use App\Models\Bill;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OfficerPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->opd = Opd::factory()->create();
        $this->type = RetributionType::factory()->create(['opd_id' => $this->opd->id, 'tariff_percent' => 10]);
        $this->classification = RetributionClassification::factory()->create([
            'retribution_type_id' => $this->type->id,
            'opd_id' => $this->opd->id,
        ]);
        $this->petugas = User::factory()->create(['role' => 'petugas', 'opd_id' => $this->opd->id]);
    }

    protected function makeCitizen(): Taxpayer
    {
        return Taxpayer::factory()->create([
            'opd_id' => $this->opd->id,
            'password' => Hash::make('secret123'),
            'aft_enabled' => false,
        ]);
    }

    protected function makeBill(Taxpayer $taxpayer, float $amount = 100000): Bill
    {
        $taxObject = TaxObject::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
        ]);

        return Bill::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
            'amount' => $amount,
            'status' => 'pending',
            'due_date' => now()->addMonth(),
        ]);
    }

    public function test_citizen_creates_officer_payment_request()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $response = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.method', 'officer');
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.bill_ids.0', $bill->id);
        $this->assertStringStartsWith('MPR-', $response->json('data.token'));
        $this->assertStringContainsString('payment_request', $response->json('data.qr_payload'));
    }

    public function test_officer_payment_request_serves_server_generated_qr_svg()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $response = $this->actingAs($citizen)->get('/api/citizen/payment-requests/' . $created['id'] . '/qr');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_qr_endpoint_rejects_non_officer_method()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'qris',
        ])->json('data');

        $response = $this->actingAs($citizen)->get('/api/citizen/payment-requests/' . $created['id'] . '/qr');

        $response->assertStatus(422);
    }

    public function test_officer_can_verify_payment_request_token()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $response = $this->actingAs($this->petugas)->getJson('/api/officer/payment-requests/' . $created['token']);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.payment_request.token', $created['token']);
        $response->assertJsonPath('data.active_bills.0.bill_number', $bill->bill_number);
        $response->assertJsonPath('data.taxpayer.nik', $citizen->nik);
        $response->assertJsonPath('data.aft.enabled', false);
    }

    public function test_officer_completes_payment_and_bill_becomes_lunas()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen, 100000);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $response = $this->actingAs($this->petugas)->postJson('/api/officer/payment-requests/' . $created['token'] . '/complete', [
            'payment_method' => 'cash',
            'tendered_amount' => 110000,
            'change_amount' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.payment_request.status', 'paid');
        $response->assertJsonPath('data.total_paid', 100000);
        $this->assertNotNull($response->json('data.transaction_reference'));
        $this->assertCount(1, $response->json('data.payment_request.receipts'));

        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'lunas']);
        $this->assertDatabaseHas('payments', ['bill_id' => $bill->id, 'payment_method' => 'cash', 'status' => 'success']);
        $this->assertDatabaseHas('payment_requests', ['id' => $created['id'], 'status' => 'paid']);
    }

    public function test_officer_payment_is_anti_double_payment()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);
        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $this->actingAs($this->petugas)->postJson('/api/officer/payment-requests/' . $created['token'] . '/complete', [
            'payment_method' => 'cash',
        ])->assertStatus(200);

        $second = $this->actingAs($this->petugas)->postJson('/api/officer/payment-requests/' . $created['token'] . '/complete', [
            'payment_method' => 'qris',
        ]);

        $second->assertStatus(422);
        $this->assertSame(1, Payment::where('bill_id', $bill->id)->count());
    }

    public function test_aft_transaction_created_when_citizen_enrolled()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen, 100000);

        AftRegistration::create([
            'taxpayer_id' => $citizen->id,
            'approval_status' => 'approved',
            'bank' => 'Bank Sultra',
            'beneficiary_account' => '9001234567',
            'beneficiary_name' => $citizen->name,
        ]);
        $citizen->update(['aft_enabled' => true]);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $response = $this->actingAs($this->petugas)->postJson('/api/officer/payment-requests/' . $created['token'] . '/complete', [
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(200);
        $this->assertSame(10000.0, (float) $response->json('data.tax_deduction'));
        $this->assertSame(90000.0, (float) $response->json('data.amount_wp'));
        $this->assertSame('success', $response->json('data.aft_transaction.status'));
        $this->assertDatabaseHas('auto_deduct_logs', [
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
        ]);
    }

    public function test_merchant_submit_omzet_and_history()
    {
        $citizen = $this->makeCitizen();
        AftRegistration::create([
            'taxpayer_id' => $citizen->id,
            'approval_status' => 'approved',
        ]);
        $citizen->update(['aft_enabled' => true]);

        $submit = $this->actingAs($citizen)->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 500000,
        ]);

        $submit->assertStatus(201);
        $submit->assertJsonPath('success', true);
        $submit->assertJsonPath('data.tax_amount', 50000);

        $history = $this->actingAs($citizen)->getJson('/api/citizen/merchant/aft-history');
        $history->assertStatus(200);
        $history->assertJsonPath('success', true);
        $history->assertJsonPath('data.history.0.status', 'success');
        $history->assertJsonPath('data.stats.total_tax_this_month', 50000);
        $history->assertJsonPath('data.stats.total_omzet', 500000);
        $history->assertJsonPath('data.stats.success_count', 1);
    }

    public function test_citizen_bills_enriched_with_payment_request()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $response = $this->actingAs($citizen)->getJson('/api/citizen/bills?per_page=50');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $bill->id);
        $response->assertJsonPath('data.0.can_pay', true);
        $response->assertJsonPath('data.0.active_payment_request.id', $created['id']);
        $response->assertJsonPath('data.0.active_payment_request.method', 'officer');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'bill_number', 'amount', 'total_amount', 'status', 'payment_options', 'active_payment_request'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_webhook_marks_bri_va_payment_as_paid()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->json('data');

        $this->assertNotNull($created['va_number']);
        $this->assertNotEmpty($created['instructions']);

        $webhook = $this->postJson('/api/webhooks/payment', [
            'order_id' => $created['external_id'],
            'transaction_status' => 'settlement',
        ]);

        $webhook->assertStatus(200);
        $webhook->assertJsonPath('success', true);

        $this->assertDatabaseHas('payment_requests', ['id' => $created['id'], 'status' => 'paid']);
    }

    public function test_webhook_creates_payment_and_marks_bill_lunas()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->json('data');

        $this->postJson('/api/webhooks/payment', [
            'order_id' => $created['external_id'],
            'transaction_status' => 'settlement',
        ])->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'payment_request_id' => $created['id'],
            'payment_method' => 'va',
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'lunas']);
    }

    public function test_webhook_creates_aft_transaction_when_enrolled()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen, 100000);

        AftRegistration::create([
            'taxpayer_id' => $citizen->id,
            'approval_status' => 'approved',
            'bank' => 'Bank Sultra',
            'beneficiary_account' => '9001234567',
            'beneficiary_name' => $citizen->name,
        ]);
        $citizen->update(['aft_enabled' => true]);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->json('data');

        $this->postJson('/api/webhooks/payment', [
            'order_id' => $created['external_id'],
            'transaction_status' => 'settlement',
        ])->assertStatus(200);

        $this->assertDatabaseHas('auto_deduct_logs', [
            'payment_request_id' => $created['id'],
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
        ]);
    }

    public function test_duplicate_webhook_does_not_duplicate_payments_or_aft()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen, 100000);

        AftRegistration::create([
            'taxpayer_id' => $citizen->id,
            'approval_status' => 'approved',
            'bank' => 'Bank Sultra',
            'beneficiary_account' => '9001234567',
            'beneficiary_name' => $citizen->name,
        ]);
        $citizen->update(['aft_enabled' => true]);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'bri_va',
        ])->json('data');

        $payload = [
            'order_id' => $created['external_id'],
            'transaction_status' => 'settlement',
        ];

        $this->postJson('/api/webhooks/payment', $payload)->assertStatus(200);
        $this->postJson('/api/webhooks/payment', $payload)->assertStatus(200);

        $this->assertSame(1, Payment::where('payment_request_id', $created['id'])->count());
        $this->assertSame(1, AftTransaction::where('payment_request_id', $created['id'])->count());
        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'lunas']);
    }

    public function test_aft_settlement_process_is_idempotent_per_request()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen, 100000);

        AftRegistration::create([
            'taxpayer_id' => $citizen->id,
            'approval_status' => 'approved',
            'bank' => 'Bank Sultra',
            'beneficiary_account' => '9001234567',
            'beneficiary_name' => $citizen->name,
        ]);
        $citizen->update(['aft_enabled' => true]);

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
            'bill_ids' => [$bill->id],
        ]);

        $payments = collect();
        DB::transaction(function () use ($bill, $request, &$payments) {
            $payments->push(Payment::create([
                'bill_id' => $bill->id,
                'payment_request_id' => $request->id,
                'taxpayer_id' => $bill->taxpayer_id,
                'payment_method' => 'cash',
                'amount' => (float) $bill->total_amount,
                'status' => 'success',
                'billing_period' => date('Y-m'),
                'paid_at' => now(),
            ]));
            $bill->update(['status' => 'lunas']);
        });

        $service = app(\App\Services\AftSettlementService::class);
        $first = $service->process($request, $payments);
        $second = $service->process($request, $payments);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertDatabaseCount('auto_deduct_logs', 1);
    }

    public function test_citizen_payment_request_refresh_and_cancel()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);
        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $this->actingAs($citizen)->postJson('/api/citizen/payment-requests/' . $created['id'] . '/refresh', [])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'pending');

        $cancel = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests/' . $created['id'] . '/cancel', []);
        $cancel->assertStatus(200);
        $cancel->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('bills', ['id' => $bill->id, 'status' => 'pending']);
    }
}
