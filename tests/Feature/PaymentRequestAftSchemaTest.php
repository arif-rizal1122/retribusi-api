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
use App\Models\TaxTransaction;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentRequestAftSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected Opd $opd;
    protected RetributionType $type;
    protected RetributionClassification $classification;
    protected User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::enableForeignKeyConstraints();

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

    // ========================================================================
    // SCHEMA #1: Database Schema - payment_requests table
    // ========================================================================

    /** @test */
    public function schema_payment_requests_table_has_all_required_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('payment_requests', [
                'id', 'token', 'taxpayer_id', 'method', 'provider', 'status',
                'amount', 'admin_fee', 'total_amount',
                'bill_ids', 'bill_numbers', 'va_number', 'qris_string', 'qr_payload',
                'external_id', 'reference_number', 'expired_at', 'paid_at',
                'instructions', 'can_refresh', 'can_cancel', 'receipts', 'metadata',
                'created_at', 'updated_at',
            ])
        );
    }

    /** @test */
    public function schema_payment_requests_token_is_unique()
    {
        $citizen = $this->makeCitizen();

        $first = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
            'token' => 'MPR-UNIQUE-TOKEN',
        ]);

        $this->expectException(QueryException::class);

        PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
            'token' => 'MPR-UNIQUE-TOKEN',
        ]);
    }

    /** @test */
    public function schema_payment_requests_method_and_status_have_db_defaults()
    {
        $citizen = $this->makeCitizen();

        $request = PaymentRequest::create(['taxpayer_id' => $citizen->id])->fresh();

        $this->assertEquals(PaymentRequest::METHOD_OFFICER, $request->method);
        $this->assertEquals(PaymentRequest::STATUS_PENDING, $request->status);
        $this->assertEquals(0.0, (float) $request->amount);
        $this->assertTrue($request->can_refresh);
        $this->assertTrue($request->can_cancel);
    }

    /** @test */
    public function schema_payment_requests_nullable_gateway_columns_accept_null()
    {
        $citizen = $this->makeCitizen();

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $this->assertNull($request->provider);
        $this->assertNull($request->va_number);
        $this->assertNull($request->qris_string);
        $this->assertNull($request->qr_payload);
        $this->assertNull($request->external_id);
        $this->assertNull($request->reference_number);
        $this->assertNull($request->expired_at);
        $this->assertNull($request->paid_at);
        $this->assertNull($request->instructions);
        $this->assertNull($request->receipts);
        $this->assertNull($request->metadata);
    }

    /** @test */
    public function schema_payment_requests_bill_ids_is_json_cast_to_array()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
            'bill_ids' => [$bill->id],
        ]);

        $this->assertIsArray($request->bill_ids);
        $this->assertEquals([$bill->id], $request->bill_ids);
        $this->assertCount(1, $request->bills());
    }

    /** @test */
    public function schema_payment_requests_token_is_generated_automatically_with_mpad_prefix()
    {
        $citizen = $this->makeCitizen();

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $this->assertStringStartsWith('MPR-', $request->token);
    }

    // ========================================================================
    // SCHEMA #2: Database Schema - aft_registrations table
    // ========================================================================

    /** @test */
    public function schema_aft_registrations_table_has_all_required_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('aft_registrations', [
                'id', 'taxpayer_id', 'status', 'bank', 'bank_code',
                'beneficiary_account', 'beneficiary_name',
                'surat_kuasa_url', 'submitted_at',
                'approval_status', 'notes', 'approved_by', 'approved_at', 'rejection_reason',
                'created_at', 'updated_at',
            ])
        );
    }

    /** @test */
    public function schema_aft_registrations_status_and_approval_defaults()
    {
        $citizen = $this->makeCitizen();

        $registration = AftRegistration::create(['taxpayer_id' => $citizen->id])->fresh();

        $this->assertEquals('active', $registration->status);
        $this->assertEquals(AftRegistration::APPROVAL_PENDING, $registration->approval_status);
    }

    /** @test */
    public function schema_aft_registrations_nullable_columns_accept_null()
    {
        $citizen = $this->makeCitizen();

        $registration = AftRegistration::create(['taxpayer_id' => $citizen->id]);

        $this->assertNull($registration->bank);
        $this->assertNull($registration->bank_code);
        $this->assertNull($registration->beneficiary_account);
        $this->assertNull($registration->beneficiary_name);
        $this->assertNull($registration->surat_kuasa_url);
        $this->assertNull($registration->submitted_at);
        $this->assertNull($registration->approved_by);
        $this->assertNull($registration->approved_at);
        $this->assertNull($registration->rejection_reason);
    }

    // ========================================================================
    // SCHEMA #3: Database Schema - auto_deduct_logs (ledger AFT produksi)
    // ========================================================================

    /** @test */
    public function schema_auto_deduct_logs_table_has_required_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('auto_deduct_logs', [
                'id', 'payment_id', 'payment_request_id', 'taxpayer_id', 'tax_object_id', 'bill_id',
                'source', 'transaction_type', 'transaction_amount', 'tax_amount', 'deducted_amount',
                'beneficiary_account', 'beneficiary_bank', 'reference_number', 'payment_channel',
                'status', 'escrow_settlement_status', 'settled_at', 'metadata',
                'created_at', 'updated_at',
            ])
        );
    }

    /** @test */
    public function schema_aft_transactions_payment_request_id_is_unique()
    {
        $citizen = $this->makeCitizen();
        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        AftTransaction::create([
            'payment_request_id' => $request->id,
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
        ]);

        $this->expectException(QueryException::class);

        AftTransaction::create([
            'payment_request_id' => $request->id,
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
        ]);
    }

    /** @test */
    public function schema_aft_transactions_payment_request_id_set_null_on_request_delete()
    {
        $citizen = $this->makeCitizen();
        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $transaction = AftTransaction::create([
            'payment_request_id' => $request->id,
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
        ]);

        $request->delete();

        $this->assertNull($transaction->fresh()->payment_request_id);
        $this->assertDatabaseHas('auto_deduct_logs', ['id' => $transaction->id]);
    }

    /** @test */
    public function schema_aft_transactions_status_defaults_to_pending()
    {
        $citizen = $this->makeCitizen();

        $transaction = AftTransaction::create([
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
        ])->fresh();

        $this->assertEquals(AftTransaction::STATUS_PENDING, $transaction->status);
        $this->assertNull($transaction->payment_id);
        $this->assertNull($transaction->beneficiary_account);
        $this->assertNull($transaction->settled_at);
        $this->assertNull($transaction->metadata);
    }

    // ========================================================================
    // SCHEMA #4: Columns added to existing tables (payments & taxpayers)
    // ========================================================================

    /** @test */
    public function schema_payments_table_has_payment_request_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('payments', [
                'id', 'bill_id', 'payment_request_id', 'taxpayer_id', 'tax_object_id',
                'transaction_id', 'payment_method', 'amount', 'status',
                'billing_period', 'paid_at', 'approved_by', 'proof_url',
                'tendered_amount', 'change_amount', 'metadata',
            ])
        );
    }

    /** @test */
    public function schema_taxpayers_table_has_aft_enabled_column()
    {
        $this->assertTrue(Schema::hasColumn('taxpayers', 'aft_enabled'));

        $citizen = $this->makeCitizen();
        $this->assertFalse($citizen->aft_enabled);
        $this->assertIsBool($citizen->aft_enabled);
    }

    // ========================================================================
    // SCHEMA #5: Foreign key behaviors (cascade & set null)
    // ========================================================================

    /** @test */
    public function schema_payment_requests_cascade_on_taxpayer_delete()
    {
        $citizen = $this->makeCitizen();
        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $citizen->delete();

        $this->assertDatabaseMissing('payment_requests', ['id' => $request->id]);
    }

    /** @test */
    public function schema_aft_registrations_cascade_on_taxpayer_delete()
    {
        $citizen = $this->makeCitizen();
        $registration = AftRegistration::create(['taxpayer_id' => $citizen->id]);

        $citizen->delete();

        $this->assertDatabaseMissing('aft_registrations', ['id' => $registration->id]);
    }

    /** @test */
    public function schema_aft_registrations_approved_by_set_null_on_user_delete()
    {
        $citizen = $this->makeCitizen();
        $approver = User::factory()->create(['role' => 'super_admin']);

        $registration = AftRegistration::create([
            'taxpayer_id' => $citizen->id,
            'approval_status' => AftRegistration::APPROVAL_APPROVED,
            'approved_by' => $approver->id,
        ]);

        $approver->delete();

        $this->assertNull($registration->fresh()->approved_by);
    }

    /** @test */
    public function schema_aft_transactions_set_null_on_payment_delete()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'taxpayer_id' => $citizen->id,
            'payment_method' => 'cash',
            'amount' => 100000,
            'status' => 'success',
            'billing_period' => date('Y-m'),
            'paid_at' => now(),
        ]);

        $transaction = AftTransaction::create([
            'payment_id' => $payment->id,
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'status' => AftTransaction::STATUS_SUCCESS,
        ]);

        $payment->delete();

        $this->assertNull($transaction->fresh()->payment_id);
    }

    /** @test */
    public function schema_payments_payment_request_id_set_null_on_request_delete()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'payment_request_id' => $request->id,
            'taxpayer_id' => $citizen->id,
            'payment_method' => 'cash',
            'amount' => 100000,
            'status' => 'success',
            'billing_period' => date('Y-m'),
            'paid_at' => now(),
        ]);

        $request->delete();

        $this->assertNull($payment->fresh()->payment_request_id);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    // ========================================================================
    // SCHEMA #6: Model fillable matches database columns
    // ========================================================================

    /** @test */
    public function schema_payment_request_fillable_matches_database_columns()
    {
        $fillable = (new PaymentRequest())->getFillable();
        $expected = [
            'token', 'taxpayer_id', 'method', 'provider', 'status',
            'amount', 'admin_fee', 'total_amount',
            'bill_ids', 'bill_numbers', 'va_number', 'qris_string', 'qr_payload',
            'external_id', 'reference_number', 'expired_at', 'paid_at',
            'instructions', 'can_refresh', 'can_cancel', 'receipts', 'metadata',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field '{$field}' harus ada di \$fillable model PaymentRequest");
        }
    }

    /** @test */
    public function schema_aft_registration_fillable_matches_database_columns()
    {
        $fillable = (new AftRegistration())->getFillable();
        $expected = [
            'taxpayer_id', 'status', 'bank', 'bank_code',
            'beneficiary_account', 'beneficiary_name', 'surat_kuasa_url', 'submitted_at',
            'approval_status', 'notes', 'approved_by', 'approved_at', 'rejection_reason',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field '{$field}' harus ada di \$fillable model AftRegistration");
        }
    }

    /** @test */
    public function schema_aft_transaction_fillable_matches_database_columns()
    {
        $fillable = (new AftTransaction())->getFillable();
        $expected = [
            'payment_id', 'payment_request_id', 'taxpayer_id', 'transaction_amount', 'tax_amount',
            'beneficiary_account', 'beneficiary_bank', 'status', 'settled_at', 'metadata',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field '{$field}' harus ada di \$fillable model AftTransaction");
        }
    }

    /** @test */
    public function schema_payment_fillable_includes_payment_request_id()
    {
        $fillable = (new Payment())->getFillable();
        $this->assertContains('payment_request_id', $fillable);
    }

    // ========================================================================
    // SCHEMA #7: Constant / status domain consistency
    // ========================================================================

    /** @test */
    public function schema_payment_request_status_constants_are_consistent()
    {
        $expected = ['draft', 'pending', 'paid', 'expired', 'failed', 'cancelled', 'processing_receipt'];

        foreach ($expected as $status) {
            $this->assertContains(
                $status,
                [PaymentRequest::STATUS_DRAFT, PaymentRequest::STATUS_PENDING, PaymentRequest::STATUS_PAID,
                    PaymentRequest::STATUS_EXPIRED, PaymentRequest::STATUS_FAILED, PaymentRequest::STATUS_CANCELLED,
                    PaymentRequest::STATUS_PROCESSING_RECEIPT],
                "Status '{$status}' harus didukung konstanta PaymentRequest"
            );
        }
    }

    /** @test */
    public function schema_payment_request_method_constants_are_consistent()
    {
        $expected = ['bri_va', 'qris', 'officer'];

        foreach ($expected as $method) {
            $this->assertContains(
                $method,
                [PaymentRequest::METHOD_BRI_VA, PaymentRequest::METHOD_QRIS, PaymentRequest::METHOD_OFFICER],
                "Method '{$method}' harus didukung konstanta PaymentRequest"
            );
        }
    }

    /** @test */
    public function schema_aft_transaction_status_constants_are_consistent()
    {
        $this->assertSame('pending', AftTransaction::STATUS_PENDING);
        $this->assertSame('success', AftTransaction::STATUS_SUCCESS);
        $this->assertSame('failed', AftTransaction::STATUS_FAILED);
    }

    /** @test */
    public function schema_aft_registration_approval_constants_are_consistent()
    {
        $this->assertSame('pending', AftRegistration::APPROVAL_PENDING);
        $this->assertSame('approved', AftRegistration::APPROVAL_APPROVED);
        $this->assertSame('rejected', AftRegistration::APPROVAL_REJECTED);
    }

    /** @test */
    public function schema_bill_status_domain_matches_payment_flow()
    {
        $citizen = $this->makeCitizen();

        $overdue = Bill::factory()->create([
            'taxpayer_id' => $citizen->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
            'status' => 'pending',
            'due_date' => now()->subDay(),
        ]);

        $lunas = Bill::factory()->create([
            'taxpayer_id' => $citizen->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
            'status' => 'paid',
            'due_date' => now()->addMonth(),
        ]);

        $this->assertSame('overdue', $overdue->status);
        $this->assertSame('lunas', $lunas->status);
    }

    // ========================================================================
    // SCHEMA #8: Model relationships
    // ========================================================================

    /** @test */
    public function schema_payment_request_has_taxpayer_relationship()
    {
        $citizen = $this->makeCitizen();
        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $this->assertNotNull($request->taxpayer);
        $this->assertEquals($citizen->id, $request->taxpayer->id);
    }

    /** @test */
    public function schema_taxpayer_has_aft_registration_latest_of_many()
    {
        $citizen = $this->makeCitizen();

        $old = AftRegistration::create(['taxpayer_id' => $citizen->id, 'bank' => 'Bank A']);
        $new = AftRegistration::create(['taxpayer_id' => $citizen->id, 'bank' => 'Bank B']);

        $this->assertEquals($new->id, $citizen->aftRegistration->id);
        $this->assertEquals('Bank B', $citizen->aftRegistration->bank);
    }

    /** @test */
    public function schema_aft_transaction_has_taxpayer_and_payment_relationships()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'taxpayer_id' => $citizen->id,
            'payment_method' => 'cash',
            'amount' => 100000,
            'status' => 'success',
            'billing_period' => date('Y-m'),
            'paid_at' => now(),
        ]);

        $transaction = AftTransaction::create([
            'payment_id' => $payment->id,
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'status' => AftTransaction::STATUS_SUCCESS,
        ]);

        $this->assertEquals($payment->id, $transaction->payment->id);
        $this->assertEquals($citizen->id, $transaction->taxpayer->id);
    }

    // ========================================================================
    // SCHEMA #9: API response structure (PaymentRequest.toApiArray)
    // ========================================================================

    /** @test */
    public function schema_payment_request_to_api_array_structure()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
            'bill_ids' => [$bill->id],
            'qr_payload' => json_encode(['type' => 'payment_request', 'method' => 'officer', 'token' => 'MPR-TEST']),
        ]);

        $array = $request->toApiArray();

        $expected = [
            'id', 'external_id', 'token', 'method', 'provider', 'status', 'status_label',
            'bill_ids', 'bill_numbers', 'amount', 'admin_fee', 'total_amount',
            'va_number', 'qris_string', 'qr_payload', 'expired_at', 'paid_at',
            'instructions', 'receipt_url', 'receipt_number', 'receipts',
            'reference_number', 'can_refresh', 'can_cancel', 'metadata',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $array, "Key '{$key}' harus ada di toApiArray PaymentRequest");
        }
    }

    /** @test */
    public function schema_verify_response_structure_matches_officer_app()
    {
        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $created = $this->actingAs($citizen)->postJson('/api/citizen/payment-requests', [
            'bill_ids' => [$bill->id],
            'method' => 'officer',
        ])->json('data');

        $response = $this->actingAs($this->petugas)->getJson('/api/officer/payment-requests/' . $created['token']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'payment_request' => [
                    'id', 'external_id', 'token', 'method', 'status', 'status_label',
                    'bill_ids', 'bill_numbers', 'amount', 'total_amount',
                    'expired_at', 'can_refresh', 'can_cancel', 'receipt_url', 'metadata',
                ],
                'taxpayer' => [
                    'id', 'nik', 'npwpd', 'name', 'address', 'phone', 'object_name', 'object_address',
                ],
                'active_bills' => [
                    '*' => ['id', 'bill_number', 'period', 'amount', 'admin_fee', 'penalty_amount',
                        'total_amount', 'status', 'due_date', 'retribution_type', 'classification', 'tax_object'],
                ],
                'payment_history',
                'aft' => ['enabled', 'approval_status', 'status', 'bank', 'beneficiary_account', 'beneficiary_name'],
            ],
        ]);
    }

    // ========================================================================
    // SCHEMA #10: No conflict with existing tables
    // ========================================================================

    /** @test */
    public function schema_aft_transactions_coexists_with_tax_transactions()
    {
        $citizen = $this->makeCitizen();
        $taxObject = TaxObject::factory()->create([
            'taxpayer_id' => $citizen->id,
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $this->type->id,
            'retribution_classification_id' => $this->classification->id,
        ]);

        // PBB / omzet lama: tax_transactions
        TaxTransaction::create([
            'opd_id' => $this->opd->id,
            'taxpayer_id' => $citizen->id,
            'tax_object_id' => $taxObject->id,
            'transaction_date' => now()->toDateString(),
            'amount' => 500000,
            'tax_amount' => 5000,
            'source' => 'machine',
        ]);

        // AFT: tabel baru dengan kolom sama-sama bernama tax_amount
        AftTransaction::create([
            'taxpayer_id' => $citizen->id,
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'status' => AftTransaction::STATUS_SUCCESS,
        ]);

        $this->assertDatabaseCount('tax_transactions', 1);
        $this->assertDatabaseCount('auto_deduct_logs', 1);
        $this->assertSame(5000.0, (float) TaxTransaction::first()->tax_amount);
        $this->assertSame(10000.0, (float) AftTransaction::first()->tax_amount);
    }

    /** @test */
    public function schema_payments_transaction_id_distinct_from_payment_request_token()
    {
        $this->assertTrue(Schema::hasColumn('payments', 'transaction_id'));
        $this->assertTrue(Schema::hasColumn('payment_requests', 'token'));

        $citizen = $this->makeCitizen();
        $bill = $this->makeBill($citizen);

        $request = PaymentRequest::create([
            'taxpayer_id' => $citizen->id,
            'method' => PaymentRequest::METHOD_OFFICER,
        ]);

        $payment = Payment::create([
            'bill_id' => $bill->id,
            'payment_request_id' => $request->id,
            'taxpayer_id' => $citizen->id,
            'transaction_id' => 'GATEWAY-TRX-001',
            'payment_method' => 'qris',
            'amount' => 100000,
            'status' => 'success',
            'billing_period' => date('Y-m'),
            'paid_at' => now(),
        ]);

        $this->assertNotEquals($request->token, $payment->transaction_id);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_request_id' => $request->id]);
    }
}
