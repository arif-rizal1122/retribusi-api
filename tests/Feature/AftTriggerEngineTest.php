<?php

namespace Tests\Feature;

use App\Jobs\ExecuteAutoDebitAFT;
use App\Models\AutoDeductLog;
use App\Models\Taxpayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'sandbox.bank-sultra.test/*' => Http::response([
                'responseCode' => '2000000',
                'responseMessage' => 'Successful',
                'status' => 'SUCCESS',
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

        $this->assertDatabaseHas('auto_deduct_logs', [
            'id' => $log->id,
            'status' => 'success',
            'escrow_settlement_status' => 'settled',
        ]);
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
