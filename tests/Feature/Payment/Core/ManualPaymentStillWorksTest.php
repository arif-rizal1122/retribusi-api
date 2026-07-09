<?php

namespace Tests\Feature\Payment\Core;

use App\Models\User;

class ManualPaymentStillWorksTest extends CorePaymentTestCase
{
    public function test_admin_can_still_record_manual_cash_payment(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $bill = $this->createCoreBill(['amount' => 70000]);

        $response = $this->actingAs($admin)->postJson('/api/payments', [
            'tax_object_id' => $bill->tax_object_id,
            'billing_period' => $bill->period,
            'payment_method' => 'cash',
            'amount' => 70000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->id,
            'payment_method' => 'cash',
            'status' => 'success',
        ]);
    }
}
