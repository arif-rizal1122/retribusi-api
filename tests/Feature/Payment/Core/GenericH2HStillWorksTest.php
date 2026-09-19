<?php

namespace Tests\Feature\Payment\Core;

use App\Models\PaymentChannel;
use App\Services\BillingService;
use App\Services\Payment\Drivers\BankSultraDriver;

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

    public function test_h2h_rejects_every_terminal_bill_status_alias(): void
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

        foreach (['PAID', 'paid', 'lunas', 'success', 'settled'] as $index => $status) {
            $bill = $this->createCoreBill([
                'bill_number' => 'SKRD-H2H-PAID-'.$index,
                'amount' => 60000,
                'status' => $status,
            ]);
            $timestamp = now()->toDateTimeString();
            $reference = 'NTB-PAID-ALIAS-'.$index;
            $signature = hash_hmac('sha256', $bill->bill_number.$timestamp.'60000', 'secret_sultra_2026');

            $response = $this->postJson('/api/v1/bank/payment', [
                'bill_number' => $bill->bill_number,
                'amount_paid' => 60000,
                'transaction_id' => $reference,
                'channel' => 'ATM',
            ], [
                'X-Signature' => $signature,
                'X-Timestamp' => $timestamp,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('status', 'error')
                ->assertJsonPath('message', 'Tagihan sudah lunas');

            $this->assertDatabaseHas('bills', [
                'id' => $bill->id,
                'status' => $status,
            ]);
            $this->assertDatabaseMissing('payments', [
                'bill_id' => $bill->id,
                'reference_number' => $reference,
            ]);
        }
    }

    public function test_bank_sultra_create_va_rejects_uppercase_paid_bill_before_external_call(): void
    {
        $bill = $this->createCoreBill([
            'bill_number' => 'SKRD-SULTRA-PAID',
            'status' => 'PAID',
        ]);

        $driver = new BankSultraDriver(app(BillingService::class));
        $result = $driver->createVA($bill->bill_number);

        $this->assertSame('error', $result['status']);
        $this->assertSame(422, $result['code']);
        $this->assertSame('Tagihan sudah lunas', $result['message']);
    }
}
