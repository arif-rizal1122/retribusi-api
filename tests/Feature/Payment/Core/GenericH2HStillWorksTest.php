<?php

namespace Tests\Feature\Payment\Core;

use App\Models\PaymentChannel;

class GenericH2HStillWorksTest extends CorePaymentTestCase
{
    public function test_existing_h2h_bank_payment_flow_still_marks_bill_paid(): void
    {
        config([
            'payment.default' => 'sultra',
            'payment.drivers.sultra.secret' => 'secret_sultra_2026',
            'payment.drivers.sultra.allowed_ips' => ['127.0.0.1'],
        ]);

        PaymentChannel::create([
            'name' => 'Bank Sultra',
            'code' => 'sultra',
            'is_active' => true,
        ]);

        $bill = $this->createCoreBill([
            'bill_number' => 'SKRD-H2H-CORE',
            'amount' => 60000,
        ]);
        $timestamp = now()->toDateTimeString();
        $signature = hash_hmac('sha256', $bill->bill_number . $timestamp . 60000, 'secret_sultra_2026');

        $response = $this->postJson('/api/v1/bank/payment', [
            'bill_number' => $bill->bill_number,
            'amount_paid' => 60000,
            'ntb' => 'NTB-CORE-001',
            'channel' => 'ATM',
        ], [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'status' => 'lunas',
        ]);
    }
}
