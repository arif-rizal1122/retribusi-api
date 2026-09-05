<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class H2HBpnSpatialReverseLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverse_lookup_requires_coordinates(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/h2h/bhumi/reverse-lookup', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_reverse_lookup_returns_graceful_fallback_when_unmapped(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/h2h/bhumi/reverse-lookup', [
            'latitude'  => -5.4664,
            'longitude' => 122.6025,
            'polygon_coordinates' => [
                [-5.4663, 122.6024],
                [-5.4665, 122.6024],
                [-5.4665, 122.6026],
                [-5.4663, 122.6026],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'verification_badge' => 'UNVERIFIED_NON_SERTIPIKAT',
                ],
            ]);
    }
}
