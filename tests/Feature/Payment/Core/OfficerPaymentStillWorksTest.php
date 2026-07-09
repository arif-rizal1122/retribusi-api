<?php

namespace Tests\Feature\Payment\Core;

use App\Models\User;
use App\Models\UserRetributionAssignment;

class OfficerPaymentStillWorksTest extends CorePaymentTestCase
{
    public function test_petugas_can_still_record_transfer_payment_for_assigned_classification(): void
    {
        $bill = $this->createCoreBill(['amount' => 80000]);
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $bill->opd_id,
        ]);

        UserRetributionAssignment::create([
            'user_id' => $petugas->id,
            'retribution_type_id' => $bill->retribution_type_id,
            'retribution_classification_id' => $bill->retribution_classification_id,
            'opd_id' => $bill->opd_id,
        ]);

        $response = $this->actingAs($petugas)->postJson('/api/payments', [
            'tax_object_id' => $bill->tax_object_id,
            'billing_period' => $bill->period,
            'payment_method' => 'transfer',
            'amount' => 80000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'status' => 'lunas',
        ]);
    }
}
