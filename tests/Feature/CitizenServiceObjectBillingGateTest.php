<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitizenServiceObjectBillingGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_bills_only_include_active_owned_objects(): void
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
        ]);
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $opd->id]);
        $active = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'status' => 'active',
        ]);
        $pending = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'status' => 'pending',
        ]);

        Bill::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $active->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);
        Bill::factory()->create([
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $pending->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
        ]);

        $this->actingAs($taxpayer)
            ->getJson("/api/citizen/services/{$classification->id}/bills")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tax_object_id', $active->id);

        $this->actingAs($taxpayer)
            ->getJson("/api/citizen/services/{$classification->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.bills')
            ->assertJsonPath('data.bills.0.tax_object_id', $active->id);
    }

    public function test_internal_user_cannot_use_citizen_service_registration_endpoints(): void
    {
        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create(['opd_id' => $opd->id]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
        ]);

        Sanctum::actingAs(User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]));

        $this->getJson('/api/citizen/services')->assertForbidden();
        $this->getJson('/api/citizen/services/pending-periods')->assertForbidden();
        $this->getJson("/api/citizen/services/{$classification->id}")->assertForbidden();
        $this->getJson("/api/citizen/services/{$classification->id}/bills")->assertForbidden();
        $this->postJson("/api/citizen/services/{$classification->id}/register", [
            'name' => 'Internal attempt',
            'address' => 'Should be rejected before validation',
        ])->assertForbidden();
    }
}
