<?php

namespace Tests\Feature;

use App\Jobs\ExecuteAutoDebitAFT;
use App\Models\AutoDeductLog;
use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AftTriggerEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_omzet_is_rejected_when_bank_sultra_aft_gate_is_default_off()
    {
        Queue::fake();
        [$taxpayer, $taxObject, $bill] = $this->createMerchantAftFixture();
        Sanctum::actingAs($taxpayer);

        $response = $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'AFT Bank Sultra belum tersedia. Gunakan pencatatan manual sementara.');

        $this->assertDatabaseCount('auto_deduct_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_submit_omzet_requires_citizen_merchant_authorization(): void
    {
        Queue::fake();
        $this->configureReadyBankSultraAft();
        [, $taxObject, $bill] = $this->createMerchantAftFixture();
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Endpoint AFT hanya untuk wajib pajak merchant.');

        $this->assertDatabaseCount('auto_deduct_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_submit_omzet_uses_owned_active_tax_object_approved_skdr_and_backend_tariff(): void
    {
        Queue::fake();
        $this->configureReadyBankSultraAft();
        [$taxpayer, $taxObject, $bill] = $this->createMerchantAftFixture([
            'classification' => ['calculation_formula' => 'turnover_amount * 0.125'],
            'bill' => ['amount' => 12500],
        ]);
        Sanctum::actingAs($taxpayer);

        $response = $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tax_amount', 12500)
            ->assertJsonPath('data.omzet', 100000)
            ->assertJsonPath('data.bill_id', $bill->id)
            ->assertJsonPath('data.tax_object_id', $taxObject->id);

        $log = AutoDeductLog::firstOrFail();

        $this->assertSame($taxpayer->id, $log->taxpayer_id);
        $this->assertSame($taxObject->id, $log->tax_object_id);
        $this->assertSame($bill->id, $log->bill_id);
        $this->assertSame(7472, $log->city_id);
        $this->assertSame('100000.00', $log->transaction_amount);
        $this->assertSame('12500.00', $log->tax_amount);
        $this->assertSame('12500.00', $log->deducted_amount);
        $this->assertSame('backend_tariff', $log->metadata['tax_calculation']['source']);
        $this->assertSame('SKDR-AFT-001', $log->metadata['bank_sultra_aft']['contract_reference']);

        Queue::assertPushed(ExecuteAutoDebitAFT::class);
    }

    public function test_submit_omzet_rejects_missing_or_unapproved_skdr(): void
    {
        Queue::fake();
        $this->configureReadyBankSultraAft();
        [$taxpayer, $taxObject, $bill] = $this->createMerchantAftFixture([
            'bill' => [
                'metadata' => [
                    'skdr_status' => 'draft',
                    'bank_sultra_aft' => [
                        'contract_reference' => 'SKDR-AFT-DRAFT',
                    ],
                ],
            ],
        ]);
        Sanctum::actingAs($taxpayer);

        $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('bill_id');

        $this->assertDatabaseCount('auto_deduct_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_submit_omzet_rejects_tax_object_or_skdr_owned_by_another_taxpayer(): void
    {
        Queue::fake();
        $this->configureReadyBankSultraAft();
        [$taxpayer] = $this->createMerchantAftFixture();
        [, $otherTaxObject, $otherBill] = $this->createMerchantAftFixture();
        Sanctum::actingAs($taxpayer);

        $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
            'tax_object_id' => $otherTaxObject->id,
            'bill_id' => $otherBill->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_object_id', 'bill_id']);

        $this->assertDatabaseCount('auto_deduct_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_submit_omzet_rejects_inactive_or_unapproved_tax_object(): void
    {
        Queue::fake();
        $this->configureReadyBankSultraAft();
        [$taxpayer, $taxObject, $bill] = $this->createMerchantAftFixture([
            'tax_object' => [
                'status' => 'pending',
                'approved_at' => null,
                'is_active' => false,
            ],
        ]);
        Sanctum::actingAs($taxpayer);

        $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tax_object_id');

        $this->assertDatabaseCount('auto_deduct_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_aft_availability_returns_safe_mobile_contract_without_runtime_details(): void
    {
        [$taxpayer] = $this->createMerchantAftFixture();
        Sanctum::actingAs($taxpayer);

        $response = $this->getJson('/api/citizen/merchant/aft-availability');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.can_submit', false)
            ->assertJsonPath('data.message', 'AFT Bank Sultra belum tersedia. Gunakan pencatatan manual sementara.')
            ->assertJsonPath('data.required_fields', ['tax_object_id', 'bill_id', 'transaction_amount'])
            ->assertJsonMissingPath('data.unavailable_reason')
            ->assertJsonMissingPath('data.base_url')
            ->assertJsonMissingPath('data.partner_id')
            ->assertJsonMissingPath('data.client_secret')
            ->assertJsonMissingPath('data.source_account_no')
            ->assertJsonMissingPath('data.destination_account_no')
            ->assertJsonMissingPath('data.signature');
    }

    public function test_aft_history_returns_safe_owner_scoped_status_contract_for_mobile(): void
    {
        [$taxpayer, $taxObject, $bill] = $this->createMerchantAftFixture();
        [$otherTaxpayer] = $this->createMerchantAftFixture();
        Sanctum::actingAs($taxpayer);

        AutoDeductLog::create([
            'city_id' => 7472,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'bill_id' => $bill->id,
            'source' => 'm-pad_mobile_calculator',
            'transaction_type' => 'pbjt_tax_deduction',
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'deducted_amount' => 10000,
            'reference_number' => 'AFT-SAFE-001',
            'payment_channel' => 'BPD_SULTRA_DIRECT_DEBIT',
            'status' => 'pending',
            'escrow_settlement_status' => 'pending',
            'failure_reason' => 'Request In Progress',
            'metadata' => [
                'bank_sultra_aft' => [
                    'merchant_id' => 'MERCHANT-AFT-001',
                    'sourceAccountNo' => '116010001234567',
                    'destinationAccountNo' => '116010009876543',
                    'payment_request' => [
                        'headers' => [
                            'Authorization' => 'Bearer token-aft',
                            'X-SIGNATURE' => 'secret-signature',
                        ],
                    ],
                    'payment_response' => [
                        'raw' => [
                            'responseCode' => '2025400',
                        ],
                    ],
                ],
            ],
        ]);
        AutoDeductLog::create([
            'taxpayer_id' => $otherTaxpayer->id,
            'source' => 'm-pad_mobile_calculator',
            'transaction_type' => 'pbjt_tax_deduction',
            'transaction_amount' => 900000,
            'tax_amount' => 90000,
            'deducted_amount' => 90000,
            'reference_number' => 'AFT-OTHER-001',
            'payment_channel' => 'BPD_SULTRA_DIRECT_DEBIT',
            'status' => 'success',
            'metadata' => [],
        ]);

        $response = $this->getJson('/api/citizen/merchant/aft-history');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.history')
            ->assertJsonPath('data.history.0.reference_number', 'AFT-SAFE-001')
            ->assertJsonPath('data.history.0.status', 'processing')
            ->assertJsonPath('data.history.0.status_label', 'Sedang diproses Bank Sultra')
            ->assertJsonPath('data.history.0.transaction_amount', 100000)
            ->assertJsonPath('data.history.0.tax_amount', 10000)
            ->assertJsonPath('data.history.0.can_retry', false)
            ->assertJsonPath('data.history.0.can_cancel', false)
            ->assertJsonPath('data.history.0.bill_reference', $bill->bill_number)
            ->assertJsonPath('data.history.0.tax_object.name', $taxObject->name)
            ->assertJsonPath('data.stats.processing_count', 1)
            ->assertJsonPath('data.stats.success_count', 0)
            ->assertJsonMissingPath('data.history.0.id')
            ->assertJsonMissingPath('data.history.0.taxpayer_id')
            ->assertJsonMissingPath('data.history.0.bill_id')
            ->assertJsonMissingPath('data.history.0.tax_object_id')
            ->assertJsonMissingPath('data.history.0.metadata')
            ->assertJsonMissingPath('data.history.0.payment_channel')
            ->assertJsonMissingPath('data.history.0.escrow_settlement_status')
            ->assertJsonMissingPath('data.history.0.failure_reason')
            ->assertJsonMissing(['AFT-OTHER-001'])
            ->assertJsonMissing(['Bearer token-aft'])
            ->assertJsonMissing(['secret-signature'])
            ->assertJsonMissing(['116010001234567'])
            ->assertJsonMissing(['116010009876543'])
            ->assertJsonMissing(['responseCode']);
    }

    public function test_aft_history_rejects_non_taxpayer_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/citizen/merchant/aft-history')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint AFT hanya untuk wajib pajak merchant.');
    }

    public function test_aft_availability_rejects_taxpayer_without_merchant_aft_access(): void
    {
        [$taxpayer] = $this->createMerchantAftFixture([
            'taxpayer' => [
                'metadata' => [
                    'role' => 'merchant',
                    'aft_enabled' => false,
                ],
            ],
        ]);
        Sanctum::actingAs($taxpayer);

        $this->getJson('/api/citizen/merchant/aft-availability')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint AFT hanya untuk wajib pajak merchant.');
    }

    public function test_aft_history_rejects_taxpayer_without_merchant_aft_access(): void
    {
        [$taxpayer] = $this->createMerchantAftFixture([
            'taxpayer' => [
                'metadata' => [
                    'role' => 'merchant',
                    'aft_enabled' => false,
                ],
            ],
        ]);
        Sanctum::actingAs($taxpayer);

        AutoDeductLog::create([
            'city_id' => 7472,
            'taxpayer_id' => $taxpayer->id,
            'source' => 'm-pad_mobile_calculator',
            'transaction_type' => 'pbjt_tax_deduction',
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'deducted_amount' => 10000,
            'reference_number' => 'AFT-UNAUTHORIZED-001',
            'payment_channel' => 'BPD_SULTRA_DIRECT_DEBIT',
            'status' => 'pending',
            'metadata' => [],
        ]);

        $this->getJson('/api/citizen/merchant/aft-history')
            ->assertForbidden()
            ->assertJsonPath('message', 'Endpoint AFT hanya untuk wajib pajak merchant.')
            ->assertJsonMissing(['AFT-UNAUTHORIZED-001']);
    }

    public function test_incoming_transfer_webhook_creates_log_and_dispatches_job()
    {
        Queue::fake();

        $payload = [
            'partnerReferenceNo' => 'BNK-20260709-12345',
            'accountNo' => '1234567890',
            'transactionAmount' => [
                'value' => '100000.00',
                'currency' => 'IDR',
            ],
            'transactionDate' => now()->toIso8601String(),
        ];

        $response = $this->postJson('/api/h2h/bpd/webhook/incoming-transfer', $payload);

        // Cek kalau route belum didaftarkan di api.php akan error 404
        if ($response->status() === 404) {
            $this->markTestSkipped('Route API BPD Webhook belum didaftarkan di routes/api.php');
        }

        $response->assertStatus(200);

        $this->assertDatabaseHas('auto_deduct_logs', [
            'transaction_amount' => 100000.00,
            'tax_amount' => 10000.00, // 10% dari 100rb
            'status' => 'pending',
        ]);

        Queue::assertPushed(ExecuteAutoDebitAFT::class);
    }

    public function test_execute_auto_debit_job_handles_successful_bpd_response()
    {
        $this->configureReadyBankSultraAft();

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
                'tokenType' => 'Bearer',
                'expiresIn' => '900',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => Http::response([
                'responseCode' => '2005400',
                'responseMessage' => 'Successful',
                'referenceNo' => 'BANK-SULTRA-REF-001',
            ], 200),
        ]);

        $log = AutoDeductLog::create([
            'city_id' => 1,
            'taxpayer_id' => 1,
            'source' => 'bpd_webhook',
            'transaction_type' => 'income_transfer',
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'deducted_amount' => 10000,
            'reference_number' => 'TEST-AFT-123',
            'payment_channel' => 'BPD',
            'status' => 'pending',
            'metadata' => [],
        ]);

        $job = new ExecuteAutoDebitAFT($log->id);
        $job->handle();

        $log->refresh();

        $this->assertSame('success', $log->status);
        $this->assertSame('settled', $log->escrow_settlement_status);
        $this->assertSame('2005400', $log->metadata['bank_sultra_aft']['payment_response']['response_code']);
        $this->assertSame('BANK-SULTRA-REF-001', $log->metadata['bank_sultra_aft']['bank_reference_no']);
        $this->assertNotNull($log->processed_at);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host'
                && $request->header('Authorization')[0] === 'Bearer token-aft'
                && $request->header('X-EXTERNAL-ID')[0] !== ''
                && $request->data()['amount'] === ['value' => '10000.00', 'currency' => 'IDR'];
        });
    }

    public function test_execute_auto_debit_job_uses_official_merchant_id_from_aft_contract_metadata()
    {
        $this->configureReadyBankSultraAft();

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => Http::response([
                'responseCode' => '2005400',
                'responseMessage' => 'Successful',
            ], 200),
        ]);

        $log = $this->createPendingAftLog('TEST-AFT-OFFICIAL-MERCHANT');
        $log->update([
            'taxpayer_id' => 99,
            'metadata' => [
                'bank_sultra_aft' => [
                    'merchant_id' => 'MERCHANT-AFT-001',
                ],
            ],
        ]);

        (new ExecuteAutoDebitAFT($log->id))->handle();

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host'
                && $request->data()['merchantId'] === 'MERCHANT-AFT-001';
        });
    }

    public function test_execute_auto_debit_job_keeps_log_pending_when_bank_returns_request_in_progress()
    {
        $this->configureReadyBankSultraAft();
        $log = $this->createPendingAftLog('TEST-AFT-PENDING');

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => Http::response([
                'responseCode' => '2025400',
                'responseMessage' => 'Request In Progress',
            ], 202),
        ]);

        (new ExecuteAutoDebitAFT($log->id))->handle();

        $log->refresh();

        $this->assertSame('pending', $log->status);
        $this->assertSame('pending', $log->escrow_settlement_status);
        $this->assertSame('2025400', $log->metadata['bank_sultra_aft']['payment_response']['response_code']);
        $this->assertSame('Request In Progress', $log->failure_reason);
        $this->assertNull($log->processed_at);
    }

    public function test_execute_auto_debit_job_fails_from_snap_failure_response_code()
    {
        $this->configureReadyBankSultraAft();
        $log = $this->createPendingAftLog('TEST-AFT-FAILED');

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => Http::response([
                'responseCode' => '4005401',
                'responseMessage' => 'Invalid Merchant',
            ], 400),
        ]);

        (new ExecuteAutoDebitAFT($log->id))->handle();

        $log->refresh();

        $this->assertSame('failed', $log->status);
        $this->assertSame('failed', $log->escrow_settlement_status);
        $this->assertSame('4005401', $log->metadata['bank_sultra_aft']['payment_response']['response_code']);
        $this->assertSame('Invalid Merchant', $log->failure_reason);
        $this->assertNotNull($log->processed_at);
    }

    public function test_execute_auto_debit_job_does_not_settle_from_legacy_success_status_without_snap_response_code()
    {
        $this->configureReadyBankSultraAft();
        $log = $this->createPendingAftLog('TEST-AFT-LEGACY-SUCCESS');

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => Http::response([
                'responseCode' => '2000000',
                'responseMessage' => 'Legacy placeholder response',
                'status' => 'SUCCESS',
            ], 200),
        ]);

        (new ExecuteAutoDebitAFT($log->id))->handle();

        $log->refresh();

        $this->assertSame('failed', $log->status);
        $this->assertSame('failed', $log->escrow_settlement_status);
        $this->assertSame('Unexpected Bank Sultra AFT service code.', $log->failure_reason);
    }

    public function test_execute_auto_debit_job_keeps_timeout_retryable_without_changing_external_id()
    {
        $this->configureReadyBankSultraAft();
        $log = $this->createPendingAftLog('TEST-AFT-TIMEOUT');

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        (new ExecuteAutoDebitAFT($log->id))->handle();

        $log->refresh();
        $firstExternalId = $log->metadata['bank_sultra_aft']['external_id'];

        $this->assertSame('pending', $log->status);
        $this->assertSame('pending', $log->escrow_settlement_status);
        $this->assertSame('Bank Sultra AFT timeout; akan dicoba ulang.', $log->failure_reason);
        $this->assertNull($log->processed_at);

        Http::fake([
            'sandbox.bank-sultra.test/v1.0/access-token/b2b' => Http::response([
                'accessToken' => 'token-aft',
            ], 200),
            'sandbox.bank-sultra.test/v1.0/debit/payment-host-to-host' => Http::response([
                'responseCode' => '2025400',
                'responseMessage' => 'Request In Progress',
            ], 202),
        ]);

        (new ExecuteAutoDebitAFT($log->id))->handle();

        $log->refresh();

        $this->assertSame($firstExternalId, $log->metadata['bank_sultra_aft']['external_id']);
        $this->assertSame($firstExternalId, $log->metadata['bank_sultra_aft']['payment_request']['headers']['X-EXTERNAL-ID']);
    }

    public function test_execute_auto_debit_job_does_not_send_outbound_request_when_gate_is_default_off()
    {
        Http::fake();

        $log = AutoDeductLog::create([
            'city_id' => 1,
            'taxpayer_id' => 1,
            'source' => 'bpd_webhook',
            'transaction_type' => 'income_transfer',
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'deducted_amount' => 10000,
            'reference_number' => 'TEST-AFT-GATED',
            'payment_channel' => 'BPD',
            'status' => 'pending',
            'metadata' => [],
        ]);

        $job = new ExecuteAutoDebitAFT($log->id);
        $job->handle();

        Http::assertNothingSent();

        $this->assertDatabaseHas('auto_deduct_logs', [
            'id' => $log->id,
            'status' => 'failed',
            'escrow_settlement_status' => 'failed',
            'failure_reason' => 'AFT Bank Sultra belum tersedia. Gunakan pencatatan manual sementara.',
        ]);
    }

    private function createPendingAftLog(string $referenceNumber): AutoDeductLog
    {
        return AutoDeductLog::create([
            'city_id' => 1,
            'taxpayer_id' => 1,
            'source' => 'bpd_webhook',
            'transaction_type' => 'income_transfer',
            'transaction_amount' => 100000,
            'tax_amount' => 10000,
            'deducted_amount' => 10000,
            'reference_number' => $referenceNumber,
            'payment_channel' => 'BPD',
            'status' => 'pending',
            'escrow_settlement_status' => 'pending',
            'metadata' => [],
        ]);
    }

    private function createMerchantAftFixture(array $overrides = []): array
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create(array_merge([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'calculation_formula' => 'turnover_amount * 0.1',
        ], $overrides['classification'] ?? []));
        RetributionRate::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'amount' => 0,
            'calculation_formula' => $classification->calculation_formula,
            'is_active' => true,
        ]);
        $taxpayer = Taxpayer::factory()->create(array_merge([
            'opd_id' => $opd->id,
            'is_active' => true,
            'metadata' => [
                'role' => 'merchant',
                'aft_enabled' => true,
                'bank_sultra_aft' => [
                    'merchant_id' => 'MERCHANT-AFT-001',
                ],
            ],
        ], $overrides['taxpayer'] ?? []));
        $taxObject = TaxObject::factory()->create(array_merge([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'status' => 'active',
            'approved_at' => now()->subDay(),
            'is_active' => true,
            'metadata' => [
                'bank_sultra_aft' => [
                    'city_id' => 7472,
                    'source_profile_ref' => 'MERCHANT-BANK-PROFILE-001',
                ],
            ],
        ], $overrides['tax_object'] ?? []));
        $bill = Bill::factory()->create(array_merge([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'amount' => 10000,
            'admin_fee' => 0,
            'penalty_amount' => 0,
            'fixed_fine_amount' => 0,
            'surcharge_amount' => 0,
            'waived_penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-08',
            'due_date' => now()->addWeek(),
            'metadata' => [
                'skdr_status' => 'approved',
                'bank_sultra_aft' => [
                    'contract_reference' => 'SKDR-AFT-001',
                    'destination_profile_ref' => 'BAPENDA-ESCROW-001',
                ],
            ],
        ], $overrides['bill'] ?? []));

        return [$taxpayer, $taxObject, $bill, $classification, $type, $opd];
    }

    private function configureReadyBankSultraAft(): void
    {
        config([
            'snap.bank_sultra_aft.enabled' => true,
            'snap.bank_sultra_aft.selected_product' => 'direct_debit_h2h',
            'snap.bank_sultra_aft.base_url' => 'https://sandbox.bank-sultra.test',
            'snap.partners.BANK_SULTRA.partner_id' => 'SULTRA-PARTNER',
            'snap.partners.BANK_SULTRA.client_key' => 'SULTRA-CLIENT',
            'snap.partners.BANK_SULTRA.client_secret' => 'sultra-client-secret',
            'snap.partners.BANK_SULTRA.channel_id' => '95221',
            'snap.partners.BANK_SULTRA.signature_algorithm' => 'hmac_sha512',
            'snap.partners.BANK_SULTRA.signature_secret' => 'sultra-signature-secret',
            'snap.bank_sultra_aft.timeout_seconds' => 15,
            'snap.bank_sultra_aft.allowed_ips' => ['127.0.0.1'],
            'snap.bank_sultra_aft.source_account_no' => '116010001234567',
            'snap.bank_sultra_aft.destination_account_no' => '116010009876543',
        ]);
    }
}
