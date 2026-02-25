<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;

class PetugasPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test a petugas processing a payment and verifying bank accounts output via API if applicable.
     */
    public function test_petugas_can_fetch_bills_with_bank_accounts()
    {
        $opd = Opd::factory()->create();
        
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $opd->id
        ]);

        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        
        $bankAccountsData = [
            [
                'bank_name' => 'Bank Sultra',
                'account_number' => '1122334455',
                'qr_image_url' => 'https://res.cloudinary.com/dummy/image/upload/v123/qris-petugas.png'
            ]
        ];

        $classification = RetributionClassification::create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'Petugas API Test',
            'bank_accounts' => $bankAccountsData
        ]);

        \App\Models\UserRetributionAssignment::create([
            'user_id' => $petugas->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'opd_id' => $opd->id
        ]);

        $taxpayer = Taxpayer::factory()->create([
            'opd_id' => $opd->id
        ]);

        $taxObject = TaxObject::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);

        $bill = Bill::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'amount' => 100000,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($petugas)->getJson('/api/bills');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.id', $bill->id);
        $response->assertJsonPath('data.0.classification.id', $classification->id);
        $response->assertJsonPath('data.0.classification.bank_accounts.0.bank_name', 'Bank Sultra');
        $response->assertJsonPath('data.0.classification.bank_accounts.0.qr_image_url', 'https://res.cloudinary.com/dummy/image/upload/v123/qris-petugas.png');
    }

    /**
     * Test petugas storing a transfer payment ensures logic allows the transfer method.
     */
    public function test_petugas_can_store_transfer_payment()
    {
        $opd = Opd::factory()->create();
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $opd->id
        ]);

        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create([
            'retribution_type_id' => $type->id,
            'opd_id' => $opd->id
        ]);

        \App\Models\UserRetributionAssignment::create([
            'user_id' => $petugas->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'opd_id' => $opd->id
        ]);

        $bill = Bill::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'amount' => 50000,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($petugas)->postJson('/api/payments', [
            'bill_id' => $bill->id,
            'amount' => 50000,
            'payment_method' => 'transfer',
            'taxpayer_id' => $bill->taxpayer_id,
            'tax_object_id' => $bill->tax_object_id,
            'billing_period' => '2026-02-25',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->id,
            'payment_method' => 'transfer',
            'amount' => 50000,
        ]);
        
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'status' => 'lunas'
        ]);
    }
}
