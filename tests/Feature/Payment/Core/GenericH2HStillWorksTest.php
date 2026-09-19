<?php

namespace Tests\Feature\Payment\Core;

use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Services\BillingService;
use App\Services\Payment\Drivers\BankSultraDriver;
use App\Services\Payment\Drivers\QRISDriver;

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

    public function test_reconciliation_requires_success_payment_settled_bill_and_reference_aliases(): void
    {
        $settledBill = $this->createCoreBill([
            'bill_number' => 'SKRD-RECON-SETTLED',
            'amount' => 50000,
            'status' => 'PAID',
        ]);
        Payment::create([
            'bill_id' => $settledBill->id,
            'taxpayer_id' => $settledBill->taxpayer_id,
            'tax_object_id' => $settledBill->tax_object_id,
            'transaction_id' => 'TX-RECON-SUCCESS',
            'reference_number' => 'RECON-SUCCESS',
            'payment_method' => 'va',
            'amount' => 50000,
            'status' => 'success',
            'billing_period' => $settledBill->period,
            'paid_at' => now(),
        ]);

        $failedBill = $this->createCoreBill([
            'bill_number' => 'SKRD-RECON-FAILED',
            'amount' => 50000,
        ]);
        Payment::create([
            'bill_id' => $failedBill->id,
            'taxpayer_id' => $failedBill->taxpayer_id,
            'tax_object_id' => $failedBill->tax_object_id,
            'transaction_id' => 'TX-RECON-FAILED',
            'reference_number' => 'RECON-FAILED',
            'payment_method' => 'va',
            'amount' => 50000,
            'status' => 'failed',
            'billing_period' => $failedBill->period,
        ]);

        $sultra = new BankSultraDriver(app(BillingService::class));
        $sultraResult = $sultra->reconcile([
            ['ntb' => 'RECON-SUCCESS', 'amount' => 50000],
            ['ntb' => 'RECON-FAILED', 'amount' => 50000],
            ['ntb' => 'RECON-MISSING', 'amount' => 50000],
            ['ntb' => 'RECON-SUCCESS', 'amount' => 49000],
        ]);

        $this->assertSame(1, $sultraResult['data']['matched']);
        $this->assertSame(3, $sultraResult['data']['mismatch']);
        $this->assertSame('Status pembayaran bukan success', $sultraResult['data']['details'][0]['status']);
        $this->assertSame('Tidak Ditemukan', $sultraResult['data']['details'][1]['status']);
        $this->assertSame('Nominal Berbeda', $sultraResult['data']['details'][2]['status']);

        $qris = new QRISDriver(app(BillingService::class));
        $qrisResult = $qris->reconcile([
            ['ntb' => 'RECON-SUCCESS', 'amount' => 50000],
        ]);

        $this->assertSame(1, $qrisResult['data']['matched']);
        $this->assertSame(0, $qrisResult['data']['mismatch']);
    }
}
