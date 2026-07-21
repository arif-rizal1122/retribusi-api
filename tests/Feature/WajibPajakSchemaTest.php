<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WajibPajakSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $petugas;
    protected Opd $opd;
    protected RetributionType $retributionType;
    protected RetributionClassification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::create([
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH-TEST'
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin'
        ]);

        $this->petugas = User::create([
            'name' => 'Petugas',
            'email' => 'petugas@test.com',
            'password' => bcrypt('password'),
            'role' => 'petugas',
            'opd_id' => $this->opd->id
        ]);

        $this->retributionType = RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Sampah',
            'unit' => 'Bulan',
            'base_amount' => 50000,
        ]);

        $this->classification = RetributionClassification::create([
            'retribution_type_id' => $this->retributionType->id,
            'opd_id' => $this->opd->id,
            'name' => 'Kelas A - Rumah Tangga',
            'code' => 'KLS-A',
        ]);
    }

    // ========================================================================
    // SCHEMA #1: Database Schema - taxpayers table columns
    // ========================================================================

    /** @test */
    public function schema_taxpayers_table_has_all_required_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('taxpayers', [
                'id', 'opd_id', 'nik', 'name', 'address', 'district', 'sub_district',
                'phone', 'npwpd', 'object_name', 'object_address',
                'latitude', 'longitude',
                'is_active', 'metadata', 'created_by', 'password',
                'created_at', 'updated_at'
            ])
        );
    }

    /** @test */
    public function schema_taxpayers_nullable_columns_accept_null()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Minimal WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertNull($taxpayer->nik);
        $this->assertNull($taxpayer->address);
        $this->assertNull($taxpayer->phone);
        $this->assertNull($taxpayer->npwpd);
        $this->assertNull($taxpayer->object_name);
        $this->assertNull($taxpayer->object_address);
        $this->assertNull($taxpayer->district);
        $this->assertNull($taxpayer->sub_district);
        $this->assertNull($taxpayer->latitude);
        $this->assertNull($taxpayer->longitude);
        $this->assertNull($taxpayer->metadata);
        $this->assertNull($taxpayer->password);
    }

    /** @test */
    public function schema_taxpayers_is_active_defaults_to_true()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Active WP',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertTrue($taxpayer->is_active);
    }

    /** @test */
    public function schema_taxpayers_metadata_is_json_cast_to_array()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Metadata WP',
            'metadata' => ['foto' => 'url.jpg', 'catatan' => 'test'],
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertIsArray($taxpayer->metadata);
        $this->assertEquals('url.jpg', $taxpayer->metadata['foto']);
    }

    /** @test */
    public function schema_taxpayers_coordinate_precision_is_correct()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Koordinat WP',
            'latitude' => -5.46320000,
            'longitude' => 122.60750000,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertEquals(-5.46320000, $taxpayer->latitude);
        $this->assertEquals(122.60750000, $taxpayer->longitude);
    }

    /** @test */
    public function schema_taxpayers_nik_is_nullable_string()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'No NIK WP',
            'created_by' => $this->superAdmin->id,
        ]);
        $this->assertNull($taxpayer->nik);

        $taxpayer2 = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'With NIK WP',
            'nik' => '7405012345678901',
            'created_by' => $this->superAdmin->id,
        ]);
        $this->assertEquals('7405012345678901', $taxpayer2->nik);
    }

    // ========================================================================
    // SCHEMA #2: API Request Validation Schema
    // ========================================================================

    /** @test */
    public function schema_store_validation_requires_name_and_retribution_type_ids()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'retribution_type_ids']);
    }

    /** @test */
    public function schema_store_validation_retribution_type_ids_must_exist()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Test WP',
            'retribution_type_ids' => [99999],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['retribution_type_ids.0']);
    }

    /** @test */
    public function schema_store_validation_accepts_all_optional_fields()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Lengkap WP',
            'opd_id' => $this->opd->id,
            'nik' => '7405012345678901',
            'address' => 'Jl. Merdeka No.1',
            'district' => 'Wolio',
            'sub_district' => 'Bataraguru',
            'phone' => '08123456789',
            'npwpd' => 'P.12345678901',
            'object_name' => 'Kios Berkah',
            'object_address' => 'Jl. Pasar No.5',
            'latitude' => -5.4632,
            'longitude' => 122.6075,
            'is_active' => true,
            'retribution_type_ids' => [$this->retributionType->id],
            'retribution_classification_ids' => [$this->classification->id],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function schema_update_validation_accepts_partial_fields()
    {
        Sanctum::actingAs($this->superAdmin);

        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Update WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->putJson("/api/taxpayers/{$taxpayer->id}", [
            'name' => 'Updated Name',
        ]);
        $response->assertSuccessful();
    }

    /** @test */
    public function schema_store_validation_rejects_wrong_retribution_type_opd()
    {
        Sanctum::actingAs($this->superAdmin);

        $otherOpd = Opd::create(['name' => 'OPD Lain', 'code' => 'OPD-LAIN']);
        $otherType = RetributionType::create([
            'opd_id' => $otherOpd->id,
            'name' => 'Retribusi Lain',
            'unit' => 'Unit',
        ]);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Cross OPD WP',
            'opd_id' => $this->opd->id,
            'retribution_type_ids' => [$otherType->id],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Jenis retribusi harus milik OPD yang sama'
        ]);
    }

    // ========================================================================
    // SCHEMA #3: API Response Structure
    // ========================================================================

    /** @test */
    public function schema_store_response_returns_expected_structure()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Response Test WP',
            'opd_id' => $this->opd->id,
            'nik' => '7405012345670001',
            'address' => 'Jl. Test',
            'phone' => '08111111111',
            'npwpd' => 'P.TEST.001',
            'object_name' => 'Objek Test',
            'object_address' => 'Jl. Objek',
            'district' => 'Wolio',
            'sub_district' => 'Tomba',
            'latitude' => -5.46,
            'longitude' => 122.60,
            'is_active' => true,
            'retribution_type_ids' => [$this->retributionType->id],
            'retribution_classification_ids' => [$this->classification->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'id', 'opd_id', 'nik', 'name', 'address', 'district', 'sub_district',
                'phone', 'npwpd', 'object_name', 'object_address',
                'latitude', 'longitude', 'is_active', 'metadata',
                'created_at', 'updated_at',
                'opd' => ['id', 'name', 'code'],
                'retribution_types',
                'retribution_classifications',
                'creator',
            ]
        ]);
    }

    /** @test */
    public function schema_store_response_uses_201_status_code()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Status Code WP',
            'opd_id' => $this->opd->id,
            'retribution_type_ids' => [$this->retributionType->id],
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function schema_list_response_returns_paginated_structure()
    {
        Sanctum::actingAs($this->superAdmin);

        Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'List Test WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->getJson('/api/taxpayers');
        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'opd_id', 'is_active', 'created_at', 'updated_at']
            ],
            'current_page', 'last_page', 'per_page', 'total', 'from', 'to'
        ]);
    }

    /** @test */
    public function schema_list_response_includes_relations()
    {
        Sanctum::actingAs($this->superAdmin);

        Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Relation WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->getJson('/api/taxpayers');
        $response->assertSuccessful();

        $this->assertArrayHasKey('opd', $response->json('data.0'));
    }

    /** @test */
    public function schema_show_response_returns_expected_structure()
    {
        Sanctum::actingAs($this->superAdmin);

        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Detail WP',
            'nik' => '7405012345670002',
            'created_by' => $this->superAdmin->id,
        ]);

        $taxpayer->retributionTypes()->attach($this->retributionType->id, [
            'retribution_classification_id' => $this->classification->id,
        ]);

        $response = $this->getJson("/api/taxpayers/{$taxpayer->id}");
        $response->assertSuccessful();

        $response->assertJsonStructure([
            'data' => [
                'id', 'opd_id', 'nik', 'name', 'address', 'district', 'sub_district',
                'phone', 'npwpd', 'object_name', 'object_address',
                'latitude', 'longitude', 'is_active', 'metadata',
                'created_at', 'updated_at',
                'opd', 'retribution_types', 'retribution_classifications', 'creator',
            ],
            'related_assets',
            'payment_history',
        ]);
    }

    /** @test */
    public function schema_search_response_returns_expected_structure()
    {
        Sanctum::actingAs($this->superAdmin);

        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Search WP',
            'nik' => '7405012345670099',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->getJson("/api/taxpayers/search/{$taxpayer->nik}");
        $response->assertSuccessful();

        $response->assertJsonStructure([
            'message', 'found', 'count',
            'data' => ['id', 'name', 'nik', 'opd_id'],
            'all_assets',
        ]);
    }

    /** @test */
    public function schema_search_returns_found_false_when_not_found()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/taxpayers/search/0000000000000000');
        $response->assertSuccessful();
        $response->assertJson([
            'found' => false,
            'count' => 0,
        ]);
    }

    /** @test */
    public function schema_destroy_response_returns_expected_message()
    {
        Sanctum::actingAs($this->superAdmin);

        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Delete WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->deleteJson("/api/taxpayers/{$taxpayer->id}");
        $response->assertSuccessful();
        $response->assertJsonStructure(['message']);
    }

    // ========================================================================
    // SCHEMA #4: Pivot Table - taxpayer_retribution_type
    // ========================================================================

    /** @test */
    public function schema_pivot_table_has_required_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('taxpayer_retribution_type', [
                'taxpayer_id', 'retribution_type_id', 'retribution_classification_id',
                'custom_amount', 'notes', 'created_at', 'updated_at',
            ])
        );
    }

    /** @test */
    public function schema_pivot_custom_amount_is_nullable()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Pivot WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $taxpayer->retributionTypes()->attach($this->retributionType->id, [
            'retribution_classification_id' => $this->classification->id,
        ]);

        $pivot = $taxpayer->retributionTypes()->first()->pivot;
        $this->assertNull($pivot->custom_amount);
    }

    /** @test */
    public function schema_pivot_has_unique_constraint()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Unique Pivot WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $taxpayer->retributionTypes()->syncWithoutDetaching([$this->retributionType->id => [
            'retribution_classification_id' => $this->classification->id,
        ]]);

        $taxpayer->retributionTypes()->syncWithoutDetaching([$this->retributionType->id => [
            'retribution_classification_id' => $this->classification->id,
        ]]);

        $this->assertDatabaseCount('taxpayer_retribution_type', 1);
    }

    // ========================================================================
    // SCHEMA #5: Tax Object auto-creation from Taxpayer
    // ========================================================================

    /** @test */
    public function schema_store_creates_tax_objects_when_object_name_provided()
    {
        Sanctum::actingAs($this->superAdmin);

        $this->postJson('/api/taxpayers', [
            'name' => 'Objek WP',
            'opd_id' => $this->opd->id,
            'object_name' => 'Kios Test',
            'object_address' => 'Jl. Test',
            'latitude' => -5.46,
            'longitude' => 122.60,
            'npwpd' => 'P.OBJEK.001',
            'retribution_type_ids' => [$this->retributionType->id],
            'retribution_classification_ids' => [$this->classification->id],
        ]);

        $this->assertDatabaseHas('tax_objects', [
            'name' => 'Kios Test',
            'address' => 'Jl. Test',
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
        ]);
    }

    /** @test */
    public function schema_tax_object_nop_format_is_correct()
    {
        Sanctum::actingAs($this->superAdmin);

        $this->postJson('/api/taxpayers', [
            'name' => 'NOP Format WP',
            'opd_id' => $this->opd->id,
            'object_name' => 'Kios NOP',
            'npwpd' => 'P.NOP.001',
            'retribution_type_ids' => [$this->retributionType->id],
            'retribution_classification_ids' => [$this->classification->id],
        ]);

        $taxObject = TaxObject::where('name', 'Kios NOP')->first();
        $this->assertNotNull($taxObject);
        $this->assertStringContainsString('P.NOP.001', $taxObject->nop);
        $this->assertStringContainsString((string)$this->retributionType->id, $taxObject->nop);
        $this->assertStringContainsString((string)$this->classification->id, $taxObject->nop);
    }

    // ========================================================================
    // SCHEMA #6: Model fillable attributes
    // ========================================================================

    /** @test */
    public function schema_model_fillable_matches_database_columns()
    {
        $taxpayer = new Taxpayer();
        $fillable = $taxpayer->getFillable();

        $expectedFillable = [
            'opd_id', 'nik', 'name', 'address', 'district', 'sub_district',
            'phone', 'npwpd', 'object_name', 'object_address',
            'latitude', 'longitude', 'is_active', 'metadata', 'created_by', 'password',
        ];

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $fillable, "Field '{$field}' harus ada di \$fillable model");
        }
    }

    /** @test */
    public function schema_model_hidden_includes_password()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Hidden WP',
            'password' => bcrypt('secret'),
            'created_by' => $this->superAdmin->id,
        ]);

        $array = $taxpayer->toArray();
        $this->assertArrayNotHasKey('password', $array);
    }

    /** @test */
    public function schema_model_casts_is_active_to_boolean()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Cast WP',
            'is_active' => 1,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertIsBool($taxpayer->is_active);
        $this->assertTrue($taxpayer->is_active);
    }

    // ========================================================================
    // SCHEMA #7: Model Relationships
    // ========================================================================

    /** @test */
    public function schema_model_has_opd_relationship()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Rel WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertNotNull($taxpayer->opd);
        $this->assertEquals($this->opd->id, $taxpayer->opd->id);
    }

    /** @test */
    public function schema_model_has_creator_relationship()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Creator WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertNotNull($taxpayer->creator);
        $this->assertEquals($this->superAdmin->id, $taxpayer->creator->id);
    }

    /** @test */
    public function schema_model_has_retribution_types_relationship()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Types Rel WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $taxpayer->retributionTypes()->attach($this->retributionType->id, [
            'retribution_classification_id' => $this->classification->id,
        ]);

        $this->assertCount(1, $taxpayer->retributionTypes);
        $this->assertEquals($this->retributionType->id, $taxpayer->retributionTypes->first()->id);
    }

    /** @test */
    public function schema_model_has_retribution_classifications_relationship()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Class Rel WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $taxpayer->retributionClassifications()->attach($this->classification->id, [
            'retribution_type_id' => $this->retributionType->id,
        ]);

        $this->assertCount(1, $taxpayer->retributionClassifications);
        $this->assertEquals($this->classification->id, $taxpayer->retributionClassifications->first()->id);
    }

    /** @test */
    public function schema_model_has_tax_objects_relationship()
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'TaxObj Rel WP',
            'created_by' => $this->superAdmin->id,
        ]);

        TaxObject::create([
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
            'opd_id' => $this->opd->id,
            'name' => 'Objek Rel Test',
            'nop' => 'NOP-REL-001',
        ]);

        $this->assertCount(1, $taxpayer->taxObjects);
        $this->assertEquals('Objek Rel Test', $taxpayer->taxObjects->first()->name);
    }

    // ========================================================================
    // SCHEMA #8: Frontend TypeScript type alignment with backend
    // ========================================================================

    /** @test */
    public function schema_api_response_matches_frontend_taxpayer_interface()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Frontend Match WP',
            'opd_id' => $this->opd->id,
            'nik' => '7405012345670010',
            'address' => 'Jl. Cocok',
            'phone' => '081234567890',
            'npwpd' => 'P.FRONTEND.001',
            'object_name' => 'Objek Frontend',
            'object_address' => 'Jl. Objek Frontend',
            'is_active' => true,
            'retribution_type_ids' => [$this->retributionType->id],
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');

        $tsInterfaceFields = ['id', 'opd_id', 'nik', 'name', 'address', 'phone',
            'npwpd', 'object_name', 'object_address', 'is_active', 'metadata',
            'created_at', 'updated_at', 'opd', 'retribution_types'];

        foreach ($tsInterfaceFields as $field) {
            $this->assertArrayHasKey($field, $data,
                "Field '{$field}' harus ada di response API karena digunakan di TypeScript Taxpayer interface");
        }
    }

    /** @test */
    public function schema_api_response_has_district_and_sub_district_for_frontend()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'District WP',
            'opd_id' => $this->opd->id,
            'district' => 'Wolio',
            'sub_district' => 'Tomba',
            'retribution_type_ids' => [$this->retributionType->id],
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');

        $this->assertEquals('Wolio', $data['district']);
        $this->assertEquals('Tomba', $data['sub_district']);
    }

    /** @test */
    public function schema_api_response_has_latitude_longitude_for_frontend_map()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'Map WP',
            'opd_id' => $this->opd->id,
            'latitude' => -5.4632,
            'longitude' => 122.6075,
            'retribution_type_ids' => [$this->retributionType->id],
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');

        $this->assertEquals(-5.4632, (float)$data['latitude']);
        $this->assertEquals(122.6075, (float)$data['longitude']);
    }

    // ========================================================================
    // SCHEMA #9: Petugas Authorization Schema
    // ========================================================================

    /** @test */
    public function schema_petugas_can_only_see_own_created_taxpayers()
    {
        $otherPetugas = User::create([
            'name' => 'Petugas Lain',
            'email' => 'petugas2@test.com',
            'password' => bcrypt('password'),
            'role' => 'petugas',
            'opd_id' => $this->opd->id,
        ]);

        \App\Models\UserRetributionAssignment::create([
            'user_id' => $this->petugas->id,
            'retribution_type_id' => $this->retributionType->id,
            'opd_id' => $this->opd->id,
        ]);

        $tp1 = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Petugas WP 1',
            'created_by' => $this->petugas->id,
        ]);
        $tp1->retributionTypes()->attach($this->retributionType->id);

        $tp2 = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Petugas WP 2',
            'created_by' => $otherPetugas->id,
        ]);
        $tp2->retributionTypes()->attach($this->retributionType->id);

        $response = $this->actingAs($this->petugas)->getJson('/api/taxpayers');
        $response->assertSuccessful();

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $this->assertContains('Petugas WP 1', $names);
        $this->assertNotContains('Petugas WP 2', $names);
    }

    /** @test */
    public function schema_petugas_cannot_see_taxpayers_without_assignment()
    {
        Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Unassigned WP',
            'created_by' => $this->petugas->id,
        ]);

        $response = $this->actingAs($this->petugas)->getJson('/api/taxpayers');
        $response->assertSuccessful();

        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    /** @test */
    public function schema_super_admin_can_access_all_taxpayers()
    {
        Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'Global WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson('/api/taxpayers');
        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
    }

    // ========================================================================
    // SCHEMA #10: File Upload Field Schema
    // ========================================================================

    /** @test */
    public function schema_store_accepts_foto_lokasi_and_formulir_in_validation()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/taxpayers', [
            'name' => 'File Schema WP',
            'opd_id' => $this->opd->id,
            'retribution_type_ids' => [$this->retributionType->id],
            'foto_lokasi_open_kamera' => 'bukan-file',
            'formulir_data_dukung' => 'bukan-file',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['foto_lokasi_open_kamera']);
    }

    /** @test */
    public function schema_update_does_not_require_file_uploads()
    {
        Sanctum::actingAs($this->superAdmin);

        $taxpayer = Taxpayer::create([
            'opd_id' => $this->opd->id,
            'name' => 'File Update WP',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->putJson("/api/taxpayers/{$taxpayer->id}", [
            'name' => 'File Update WP v2',
        ]);

        $response->assertSuccessful();
    }
}
