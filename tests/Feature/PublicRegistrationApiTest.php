<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_master_data_endpoints_are_available(): void
    {
        $opd = Opd::factory()->create(['name' => 'Bapenda Public']);
        $activeType = RetributionType::factory()->create([
            'opd_id' => $opd->id,
            'name' => 'Pajak Aktif',
            'is_active' => true,
        ]);
        $inactiveType = RetributionType::factory()->create([
            'opd_id' => $opd->id,
            'name' => 'Pajak Nonaktif',
            'is_active' => false,
        ]);

        $activeClassification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $activeType->id,
            'name' => 'Restoran Aktif',
        ]);
        RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $inactiveType->id,
            'name' => 'Restoran Nonaktif',
        ]);

        $this->getJson('/api/public/opds')
            ->assertOk()
            ->assertJsonFragment(['id' => $opd->id, 'name' => 'Bapenda Public']);

        $this->getJson('/api/public/retribution-types')
            ->assertOk()
            ->assertJsonFragment(['id' => $activeType->id, 'name' => 'Pajak Aktif'])
            ->assertJsonMissing(['id' => $inactiveType->id, 'name' => 'Pajak Nonaktif']);

        $this->getJson('/api/public/retribution-classifications')
            ->assertOk()
            ->assertJsonFragment(['id' => $activeClassification->id, 'name' => 'Restoran Aktif'])
            ->assertJsonMissing(['name' => 'Restoran Nonaktif']);
    }

    public function test_public_registration_creates_pending_tax_object_and_verification(): void
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id, 'is_active' => true]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'form_schema' => [
                ['key' => 'omzet_harian', 'label' => 'Omzet Harian', 'required' => true],
            ],
            'requirements' => [],
        ]);

        $response = $this->postJson('/api/public/register-taxpayer', [
            'nik' => '7472010101010001',
            'name' => 'Wajib Pajak Public',
            'phone' => '081234567890',
            'address' => 'Jl. Domisili',
            'object_name' => 'Rumah Makan Public',
            'object_address' => 'Jl. Objek',
            'district' => 'Wolio',
            'sub_district' => 'Tomba',
            'latitude' => -5.46,
            'longitude' => 122.60,
            'opd_id' => $opd->id,
            'retribution_classification_ids' => [$classification->id],
            'metadata' => ['omzet_harian' => '1000000'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('object.status', 'pending')
            ->assertJsonPath('object.retribution_type_id', $type->id)
            ->assertJsonPath('object.retribution_classification_id', $classification->id);

        $taxpayer = Taxpayer::where('nik', '7472010101010001')->firstOrFail();
        $taxObject = TaxObject::where('taxpayer_id', $taxpayer->id)->firstOrFail();

        $this->assertFalse($taxpayer->is_active);
        $this->assertSame('pending', $taxObject->status);

        $this->assertDatabaseHas('verifications', [
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'status' => 'pending',
            'type' => 'Pendaftaran Objek',
        ]);
    }

    public function test_public_check_nik_returns_taxpayer_and_tax_object_assets(): void
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id, 'is_active' => true]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
        ]);
        $taxpayer = Taxpayer::factory()->create([
            'opd_id' => $opd->id,
            'nik' => '7472010101010002',
            'name' => 'WP Existing',
            'phone' => '081200000002',
        ]);
        $taxObject = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'name' => 'Objek Existing',
        ]);

        $this->getJson('/api/public/taxpayers/check-nik/7472010101010002')
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('data.name', 'WP Existing')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('all_assets.0.id', $taxObject->id)
            ->assertJsonPath('all_assets.0.name', 'Objek Existing');
    }
}
