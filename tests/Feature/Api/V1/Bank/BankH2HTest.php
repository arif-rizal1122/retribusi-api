<?php

namespace Tests\Feature\Api\V1\Bank;

use App\Models\Bill;
use App\Models\Taxpayer;
use App\Models\RetributionType;
use App\Models\TaxObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankH2HTest extends TestCase
{
    use RefreshDatabase;

    protected $secret = 'secret_sultra_2026';

    protected function setUp(): void
    {
        parent::setUp();
        config(['payment.drivers.sultra.secret' => $this->secret]);
        config(['payment.drivers.sultra.allowed_ips' => ['127.0.0.1']]);
    }

    public function test_h2h_inquiry_success()
    {
        $taxpayer = Taxpayer::factory()->create();
        $type = RetributionType::factory()->create(['name' => 'Persampahan']);
        $taxObject = TaxObject::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id
        ]);
        
        $bill = Bill::factory()->create([
            'bill_number' => 'SKRD-2026-TEST',
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'amount' => 50000,
            'penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-04',
            'due_date' => now()->addDays(30)
        ]);

        $timestamp = now()->toDateTimeString();
        $signature = hash_hmac('sha256', $bill->bill_number . $timestamp . $bill->amount, $this->secret);

        $response = $this->postJson('/api/v1/bank/inquiry', [
            'bill_number' => $bill->bill_number,
            'amount' => $bill->amount
        ], [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.bill_number', $bill->bill_number);
    }

    public function test_h2h_payment_success()
    {
        $taxpayer = Taxpayer::factory()->create();
        $bill = Bill::factory()->create([
            'bill_number' => 'SKRD-2026-PAY',
            'taxpayer_id' => $taxpayer->id,
            'amount' => 100000,
            'penalty_amount' => 0,
            'status' => 'pending',
            'period' => '2026-04',
            'due_date' => now()->addDays(30)
        ]);

        $timestamp = now()->toDateTimeString();
        $ntb = 'BANK-TRX-123';
        $amountPaid = 100000;
        
        $signature = hash_hmac('sha256', $bill->bill_number . $timestamp . $amountPaid, $this->secret);

        $response = $this->postJson('/api/v1/bank/payment', [
            'bill_number' => $bill->bill_number,
            'amount_paid' => $amountPaid,
            'ntb' => $ntb,
            'channel' => 'ATM'
        ], [
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('bills', [
            'bill_number' => 'SKRD-2026-PAY',
            'status' => 'lunas'
        ]);

        $this->assertDatabaseHas('payments', [
            'reference_number' => $ntb,
            'amount' => $amountPaid
        ]);
    }

    public function test_h2h_invalid_signature()
    {
        $response = $this->postJson('/api/v1/bank/inquiry', [
            'bill_number' => 'ANY-BILL',
            'amount' => 1000
        ], [
            'X-Signature' => 'WRONG-SIG',
            'X-Timestamp' => now()->toDateTimeString()
        ]);

        $response->assertStatus(401);
    }
}
