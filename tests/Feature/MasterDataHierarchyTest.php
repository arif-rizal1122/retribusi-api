<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\RetributionType;
use App\Models\RetributionClassification;
use App\Models\Zone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $opd;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->opd = Opd::create([
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH-001'
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin'
        ]);
    }

    /** @test */
    public function it_can_create_zone_with_inferred_opd_id()
    {
        $type = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Sampah',
            'unit' => 'Bulan',
            'base_amount' => 10000
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/zones', [
                'retribution_type_id' => $type->id,
                'name' => 'Zona A',
                'geometry_type' => 'point',
                'latitude' => -5.46,
                'longitude' => 122.60
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('zones', [
            'name' => 'Zona A',
            'opd_id' => $this->opd->id
        ]);
        
        // Check if code was auto-generated
        $zone = Zone::where('name', 'Zona A')->first();
        $this->assertNotNull($zone->code);
    }

    /** @test */
    public function it_handles_empty_strings_by_converting_to_null()
    {
        $type = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Sampah',
            'unit' => 'Bulan',
            'base_amount' => 10000
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/zones', [
                'retribution_type_id' => $type->id,
                'name' => 'Zona B',
                'retribution_classification_id' => '', // Should become null
                'description' => '', // Should become null
                'code' => '' // Should be auto-generated
            ]);

        $response->assertStatus(201);
        $zone = Zone::where('name', 'Zona B')->first();
        $this->assertNull($zone->retribution_classification_id);
        $this->assertNull($zone->description);
        $this->assertNotNull($zone->code);
    }

    /** @test */
    public function it_validates_required_fields_with_correct_status_code()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/zones', [
                'name' => 'Missing Type ID'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_allows_long_codes_in_update()
    {
        $type = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Sampah',
            'unit' => 'Bulan',
            'base_amount' => 10000
        ]);

        $zone = Zone::create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $type->id,
            'name' => 'Original Zone',
            'code' => 'Z-OLD'
        ]);

        $longCode = 'Z-VERY-LONG-CODE-BEYOND-TEN-CHARACTERS-TEST';
        
        $response = $this->actingAs($this->admin)
            ->putJson("/api/zones/{$zone->id}", [
                'code' => $longCode
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('zones', [
            'id' => $zone->id,
            'code' => $longCode
        ]);
    }

    /** @test */
    public function classification_creation_infers_opd_id()
    {
        $type = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Pasar',
            'unit' => 'Hari',
            'base_amount' => 2000
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/retribution-classifications', [
                'retribution_type_id' => $type->id,
                'name' => 'Kelas A',
                'code' => 'KLS-A'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('retribution_classifications', [
            'name' => 'Kelas A',
            'opd_id' => $this->opd->id
        ]);
    }
}
