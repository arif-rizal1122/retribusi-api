<?php

namespace Tests\Feature;

use App\Models\TaxObject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeatmapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_heatmap_data_returns_correct_structure()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        
        TaxObject::factory()->count(3)->create([
            'latitude' => -5.46,
            'longitude' => 122.60
        ]);

        $response = $this->actingAs($user)->getJson('/api/analytics/heatmap');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'lat',
                        'lng',
                        'total_revenue',
                        'object_count',
                        'objects'
                    ]
                ]
            ]);
    }
}
