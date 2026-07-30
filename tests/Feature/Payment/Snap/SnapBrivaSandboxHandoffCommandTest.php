<?php

namespace Tests\Feature\Payment\Snap;

use App\Models\Bill;
use App\Models\PaymentRequest;
use App\Models\PaymentRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SnapBrivaSandboxHandoffCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prints_safe_sandbox_handoff_for_pending_briva_payment_request(): void
    {
        config([
            'snap.briva.partner_service_id' => '',
            'snap.briva.va_prefix' => '19960',
            'snap.callback_base_url' => 'https://default.example.test',
        ]);

        $bill = Bill::factory()->create([
            'bill_number' => 'SKRD-SBX-001',
            'amount' => 100000,
        ]);

        $paymentRequest = PaymentRequest::create([
            'bill_id' => $bill->id,
            'tax_object_id' => $bill->tax_object_id,
            'taxpayer_id' => $bill->taxpayer_id,
            'payment_channel' => 'BRI',
            'method' => 'VA',
            'va_number' => '199600000000000003',
            'amount_snapshot' => 100000,
            'admin_fee_snapshot' => 0,
            'penalty_snapshot' => 0,
            'expires_at' => now()->addHour(),
            'external_id' => 'MOB-BRI-SANDBOX-HANDOFF',
            'status' => 'pending',
        ]);

        PaymentRequestItem::create([
            'payment_request_id' => $paymentRequest->id,
            'bill_id' => $bill->id,
            'amount_snapshot' => 100000,
            'status' => 'pending',
        ]);

        $exitCode = Artisan::call('snap:briva-sandbox-handoff', [
            'payment_request_id' => $paymentRequest->id,
            '--base-url' => 'https://apimpad.baubaukota.go.id',
            '--json' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"va_number": "199600000000000003"', $output);
        $this->assertStringContainsString('"partner_service_id": "19960"', $output);
        $this->assertStringContainsString('"customer_no": "0000000000003"', $output);
        $this->assertStringContainsString(
            '"inquiry_va": "https://apimpad.baubaukota.go.id/api/snap/v1.0/transfer-va/inquiry"',
            $output
        );
        $this->assertStringNotContainsString((string) $bill->taxpayer->nik, $output);
    }

    public function test_it_rejects_non_pending_payment_request(): void
    {
        $bill = Bill::factory()->create();
        $paymentRequest = PaymentRequest::create([
            'bill_id' => $bill->id,
            'tax_object_id' => $bill->tax_object_id,
            'taxpayer_id' => $bill->taxpayer_id,
            'payment_channel' => 'BRI',
            'method' => 'VA',
            'va_number' => '199600000000000004',
            'amount_snapshot' => 100000,
            'admin_fee_snapshot' => 0,
            'penalty_snapshot' => 0,
            'expires_at' => now()->addHour(),
            'external_id' => 'MOB-BRI-SANDBOX-PAID',
            'status' => 'paid',
        ]);

        $this->artisan('snap:briva-sandbox-handoff', [
            'payment_request_id' => $paymentRequest->id,
            '--json' => true,
        ])
            ->expectsOutputToContain('Payment request harus berstatus pending')
            ->assertExitCode(1);
    }
}
