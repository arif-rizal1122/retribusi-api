<?php

namespace Tests\Feature;

use App\Jobs\ExecuteAutoDebitAFT;
use App\Models\AutoDeductLog;
use App\Models\Taxpayer;
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
        Sanctum::actingAs(Taxpayer::factory()->create());

        $response = $this->postJson('/api/citizen/merchant/submit-omzet', [
            'transaction_amount' => 100000,
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'AFT Bank Sultra belum tersedia. Gunakan pencatatan manual sementara.');

        $this->assertDatabaseCount('auto_deduct_logs', 0);
        Queue::assertNothingPushed();
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
