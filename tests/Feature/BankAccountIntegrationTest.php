<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Opd;

class BankAccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test if bank_accounts can be stored and retrieved with qr_image_url.
     *
     * @return void
     */
    public function test_classification_stores_and_casts_bank_accounts()
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);

        $bankAccountsData = [
            [
                'bank_name' => 'Bank Sultra',
                'account_number' => '1234567890',
                'account_name' => 'Bapenda TTD',
                'qr_image_url' => 'https://res.cloudinary.com/dummy/image/upload/v123/qris.png'
            ],
            [
                'bank_name' => 'QRIS BNI',
                'account_number' => '0987654321',
            ]
        ];

        $classification = RetributionClassification::create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'Test Classification',
            'bank_accounts' => $bankAccountsData
        ]);

        $this->assertDatabaseHas('retribution_classifications', [
            'id' => $classification->id,
            'name' => 'Test Classification',
        ]);

        $freshClassification = RetributionClassification::find($classification->id);
        
        $this->assertIsArray($freshClassification->bank_accounts);
        $this->assertCount(2, $freshClassification->bank_accounts);
        $this->assertEquals('Bank Sultra', $freshClassification->bank_accounts[0]['bank_name']);
        $this->assertEquals('https://res.cloudinary.com/dummy/image/upload/v123/qris.png', $freshClassification->bank_accounts[0]['qr_image_url']);
    }

    /**
     * Test if citizenBills API correctly eager loads classification and exposes bank_accounts.
     */
    public function test_citizen_bills_api_exposes_bank_accounts()
    {
        $opd = Opd::factory()->create();
        
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        
        $bankAccountsData = [
            [
                'bank_name' => 'QRIS Mandiri',
                'account_number' => '1122334455',
                'qr_image_url' => 'https://cdn.example.com/qris-mandiri.jpg'
            ]
        ];

        $classification = RetributionClassification::create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'API Test Classification',
            'bank_accounts' => $bankAccountsData
        ]);

        $taxpayer = Taxpayer::factory()->create([
            'opd_id' => $opd->id,
            'nik' => '9988776655443322'
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
            'amount' => 50000,
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/citizen/bills?nik=9988776655443322');

        $response->assertStatus(200);
        
        // Assert the classification and its bank_accounts structure are present in the response
        $response->assertJsonPath('data.0.classification.id', $classification->id);
        $response->assertJsonPath('data.0.classification.bank_accounts.0.bank_name', 'QRIS Mandiri');
        $response->assertJsonPath('data.0.classification.bank_accounts.0.qr_image_url', 'https://cdn.example.com/qris-mandiri.jpg');
    }
}
