<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\RetributionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataPersistenceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_classification_controller_persists_bank_accounts_from_admin_form(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $bankAccounts = [
            [
                'bank_name' => 'Bank Sultra',
                'account_number' => '1234567890',
                'account_name' => 'Bendahara Penerimaan',
                'qr_image_url' => 'https://cdn.example.test/qris.png',
            ],
        ];

        $response = $this->actingAs($admin)->postJson('/api/retribution-classifications', [
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'Klasifikasi Transfer',
            'code' => 'TRF',
            'form_schema' => json_encode([]),
            'requirements' => json_encode([]),
            'bank_accounts' => json_encode($bankAccounts),
            'calculation_formula' => 'omzet * 0.1',
        ]);

        $response->assertStatus(201);

        $classification = RetributionClassification::first();
        $this->assertSame('Bank Sultra', $classification->bank_accounts[0]['bank_name']);
        $this->assertSame('https://cdn.example.test/qris.png', $classification->bank_accounts[0]['qr_image_url']);
        $this->assertSame('omzet * 0.1', $classification->calculation_formula);
    }

    public function test_rate_controller_persists_calculation_formula(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/retribution-rates', [
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'name' => 'Tarif Formula',
            'amount' => 12,
            'unit' => 'persen',
            'is_active' => true,
            'calculation_formula' => 'tagihan * 0.12',
        ]);

        $response->assertStatus(201);

        $rate = RetributionRate::first();
        $this->assertSame('tagihan * 0.12', $rate->calculation_formula);
    }
}
