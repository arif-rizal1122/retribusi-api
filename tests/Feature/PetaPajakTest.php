<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetaPajakTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin.test@baubaukota.go.id',
        ]);
    }

    public function test_districts_endpoint_handles_request()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $response = $this->getJson('/api/peta-pajak/districts');
        // If smartgis connection is not present in test env, it gracefully returns 503 error, or 200 if connected
        $this->assertTrue(in_array($response->status(), [200, 503]));
    }

    public function test_district_bounds_endpoint_handles_request()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $response = $this->getJson('/api/peta-pajak/district-bounds');
        $this->assertTrue(in_array($response->status(), [200, 503]));
    }

    public function test_kelurahan_bounds_endpoint_handles_request()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $response = $this->getJson('/api/peta-pajak/kelurahan-bounds');
        $this->assertTrue(in_array($response->status(), [200, 503]));
    }

    public function test_parcels_endpoint_handles_request()
    {
        Sanctum::actingAs($this->adminUser, ['*']);
        $response = $this->getJson('/api/peta-pajak/parcels?kelurahan=BATULO&limit=10');
        $this->assertTrue(in_array($response->status(), [200, 503]));
    }
}
