<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationBillingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_uses_formula_and_canonical_period_without_duplicate_bill(): void
    {
        Carbon::setTestNow('2026-05-08 10:00:00');

        $opd = Opd::factory()->create();
        $type = RetributionType::factory()->create([
            'opd_id' => $opd->id,
            'base_amount' => 1000,
            'billing_cycle' => 'monthly',
        ]);
        $classification = RetributionClassification::factory()->create([
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'calculation_formula' => 'tagihan * 0.1',
        ]);
        $taxpayer = Taxpayer::factory()->create(['opd_id' => $opd->id]);
        $taxObject = TaxObject::factory()->create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'status' => 'pending',
            'metadata' => ['tagihan' => 2500000],
        ]);
        $admin = User::factory()->create([
            'opd_id' => $opd->id,
            'role' => 'opd',
            'status' => 'active',
        ]);
        $verification = Verification::create([
            'opd_id' => $opd->id,
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'document_number' => 'REG-TEST',
            'taxpayer_name' => $taxpayer->name,
            'type' => 'Pendaftaran Objek',
            'amount' => 0,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/verifications/{$verification->id}/status", [
                'status' => 'approved',
                'notes' => 'Data sesuai',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tax_objects', [
            'id' => $taxObject->id,
            'status' => 'active',
            'approved_by' => $admin->id,
        ]);

        $this->assertDatabaseCount('bills', 1);
        $bill = Bill::first();

        $this->assertSame($taxObject->id, $bill->tax_object_id);
        $this->assertSame('2026-05', $bill->period);
        $this->assertSame('2026-05-01', $bill->period_start->toDateString());
        $this->assertSame('2026-05-31', $bill->period_end->toDateString());
        $this->assertEquals(250000, (float) $bill->amount);
        $this->assertSame('verification_approval', $bill->metadata['source']);
        $this->assertSame('classification_formula', $bill->metadata['calculation']['source']);

        $this->actingAs($admin)
            ->putJson("/api/verifications/{$verification->id}/status", [
                'status' => 'approved',
                'notes' => 'Approve ulang',
            ])
            ->assertStatus(200);

        $this->assertDatabaseCount('bills', 1);

        Carbon::setTestNow();
    }
}
