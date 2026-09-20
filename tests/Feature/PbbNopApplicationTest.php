<?php

namespace Tests\Feature;

use App\Models\Taxpayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PbbNopApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_pbb_nop_application_succeeds_with_seeder_test_nik(): void
    {
        $taxpayer = Taxpayer::factory()->create([
            'nik' => '0000000000000001',
            'name' => 'Wajib Pajak Test',
            'address' => 'Jl. Testing No. 1, Baubau',
        ]);

        Sanctum::actingAs($taxpayer, ['*']);

        $akteFile = UploadedFile::fake()->create('akte.pdf', 150, 'application/pdf');

        $response = $this->post('/api/citizen/pbb/nop-applications', [
            'nik' => $taxpayer->nik,
            'name' => $taxpayer->name,
            'address' => $taxpayer->address,
            'latitude' => -5.4664,
            'longitude' => 122.6025,
            'land_area' => '150',
            'building_area' => '80',
            'registered_for' => 'self',
            'akte_file' => $akteFile,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Application submitted successfully',
            ]);

        $this->assertDatabaseHas('pbb_nop_applications', [
            'user_id' => $taxpayer->id,
            'nik' => '0000000000000001',
            'status' => 'PENDING',
        ]);
    }

    public function test_store_pbb_nop_application_for_other_without_owner_address_succeeds(): void
    {
        $taxpayer = Taxpayer::factory()->create([
            'nik' => '7472010101010001',
            'name' => 'Pelapor Warga',
            'address' => 'Jl. Pelapor No. 2, Baubau',
        ]);

        Sanctum::actingAs($taxpayer, ['*']);

        $akteFile = UploadedFile::fake()->create('akte.pdf', 150, 'application/pdf');
        $ktpFile = UploadedFile::fake()->create('ktp.pdf', 150, 'application/pdf');

        $response = $this->post('/api/citizen/pbb/nop-applications', [
            'nik' => $taxpayer->nik,
            'name' => $taxpayer->name,
            'address' => 'Jl. Lokasi Objek No. 5, Baubau',
            'latitude' => -5.4664,
            'longitude' => 122.6025,
            'land_area' => '200',
            'registered_for' => 'other',
            'owner_nik' => '7472010101010002',
            'owner_name' => 'Pemilik Asli',
            'akte_file' => $akteFile,
            'ktp_file' => $ktpFile,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('pbb_nop_applications', [
            'registered_for' => 'other',
            'owner_name' => 'Pemilik Asli',
            'owner_address' => 'Jl. Lokasi Objek No. 5, Baubau',
        ]);
    }
}
