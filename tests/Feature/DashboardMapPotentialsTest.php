<?php

namespace Tests\Feature;

use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMapPotentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_potentials_only_returns_active_tax_objects(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $activeObject = TaxObject::factory()->create([
            'name' => 'Objek Aktif',
            'status' => 'active',
            'latitude' => -5.4601,
            'longitude' => 122.6001,
        ]);

        $pendingObject = TaxObject::factory()->create([
            'name' => 'Objek Pending',
            'status' => 'pending',
            'latitude' => -5.4602,
            'longitude' => 122.6002,
        ]);

        $rejectedObject = TaxObject::factory()->create([
            'name' => 'Objek Ditolak',
            'status' => 'rejected',
            'latitude' => -5.4603,
            'longitude' => 122.6003,
        ]);

        $inactiveTaxpayer = Taxpayer::factory()->create(['is_active' => false]);
        $inactiveTaxpayerObject = TaxObject::factory()->create([
            'taxpayer_id' => $inactiveTaxpayer->id,
            'name' => 'Objek Akun Nonaktif',
            'status' => 'active',
            'latitude' => -5.4604,
            'longitude' => 122.6004,
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/map-potentials');

        $response->assertOk();

        $taxObjectIds = collect($response->json())
            ->where('status', 'taxpayer')
            ->pluck('tax_object_id');

        $this->assertTrue($taxObjectIds->contains($activeObject->id));
        $this->assertFalse($taxObjectIds->contains($pendingObject->id));
        $this->assertFalse($taxObjectIds->contains($rejectedObject->id));
        $this->assertFalse($taxObjectIds->contains($inactiveTaxpayerObject->id));
    }
}
