<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrontonBundlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_exposes_straight_line_basis_and_requires_route_confirmation(): void
    {
        $opd = Opd::factory()->create([
            'name' => 'Dinas PUPR Kota Baubau',
        ]);
        $user = User::factory()->create([
            'role' => 'admin',
            'opd_id' => $opd->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/pupr/tronton/calculate', [
            'latitude' => -5.4633,
            'longitude' => 122.6012,
            'is_round_trip' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.distance_basis', 'straight_line_estimate')
            ->assertJsonPath('data.route_confirmation_required', true)
            ->assertJsonPath('data.is_round_trip', false);

        $this->assertGreaterThan(0, (float) $response->json('data.distance_km'));
        $this->assertGreaterThan(0, (float) $response->json('data.total_mobilization_fee'));
    }
}
