<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserLocationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $paths = [
            'database/migrations/0001_01_00_000000_create_opds_table.php',
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2026_01_29_093218_create_personal_access_tokens_table.php',
            'database/migrations/2026_02_14_154924_create_audit_logs_table.php',
            'database/migrations/2026_02_26_094037_add_location_to_users_table.php',
        ];

        foreach ($paths as $path) {
            $this->artisan('migrate', ['--path' => $path, '--force' => true]);
        }

        $this->user = User::create([
            'name' => 'Petugas Lapangan',
            'email' => 'petugas@test.com',
            'password' => bcrypt('password'),
            'role' => 'petugas',
            'status' => 'active',
        ]);
    }

    public function test_authenticated_user_can_update_location(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/user/location', [
            'latitude' => -5.4641,
            'longitude' => 122.6112,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Lokasi diperbarui',
                'latitude' => -5.4641,
                'longitude' => 122.6112,
            ]);
    }

    public function test_location_update_persists_to_database(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/user/location', [
            'latitude' => -5.4701,
            'longitude' => 122.5993,
        ])->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'latitude' => -5.4701,
            'longitude' => 122.5993,
        ]);

        $fresh = $this->user->fresh();
        $this->assertEquals(-5.4701, (float) $fresh->latitude);
        $this->assertEquals(122.5993, (float) $fresh->longitude);
    }

    public function test_location_update_accepts_negative_coordinates(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/user/location', [
            'latitude' => -5.47,
            'longitude' => 122.6,
        ])->assertStatus(200);
    }

    public function test_location_update_requires_latitude_and_longitude(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/user/location', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_location_update_rejects_non_numeric_coordinates(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/user/location', [
            'latitude' => 'abc',
            'longitude' => 'xyz',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    public function test_location_update_requires_authentication(): void
    {
        $response = $this->putJson('/api/user/location', [
            'latitude' => -5.4641,
            'longitude' => 122.6112,
        ]);

        $response->assertStatus(401);
    }

    public function test_location_update_does_not_crash_for_new_user(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/user/location', [
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Lokasi diperbarui']);
    }
}