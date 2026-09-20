<?php

namespace Tests\Feature;

use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PbbNopApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_pbb_nop_application_succeeds_for_vacant_land(): void
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
            'has_building' => 0,
            'building_area' => '0',
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
            'building_area' => 0,
        ]);
    }

    public function test_store_pbb_nop_application_with_building_and_pbg(): void
    {
        $taxpayer = Taxpayer::factory()->create([
            'nik' => '0000000000000001',
            'name' => 'Wajib Pajak Test',
            'address' => 'Jl. Testing No. 1, Baubau',
        ]);

        Sanctum::actingAs($taxpayer, ['*']);

        $akteFile = UploadedFile::fake()->create('akte.pdf', 150, 'application/pdf');
        $pbgFile = UploadedFile::fake()->create('pbg.pdf', 150, 'application/pdf');

        $response = $this->post('/api/citizen/pbb/nop-applications', [
            'nik' => $taxpayer->nik,
            'name' => $taxpayer->name,
            'address' => $taxpayer->address,
            'latitude' => -5.4664,
            'longitude' => 122.6025,
            'land_area' => '200',
            'has_building' => 1,
            'building_area' => '140',
            'building_floors' => 2,
            'building_usage' => 'Rumah Tinggal',
            'pbg_number' => 'PBG-747201-20092026-001',
            'pbg_date' => '15 September 2026',
            'registered_for' => 'self',
            'akte_file' => $akteFile,
            'imb_file' => $pbgFile,
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
            'building_area' => 140,
        ]);
    }

    public function test_store_pbb_nop_application_for_other_requires_ktp_and_surat_kuasa(): void
    {
        $taxpayer = Taxpayer::factory()->create([
            'nik' => '7472010101010001',
            'name' => 'Pelapor Warga',
            'address' => 'Jl. Pelapor No. 2, Baubau',
        ]);

        Sanctum::actingAs($taxpayer, ['*']);

        $akteFile = UploadedFile::fake()->create('akte.pdf', 150, 'application/pdf');
        $ktpFile = UploadedFile::fake()->create('ktp.pdf', 150, 'application/pdf');
        $suratKuasaFile = UploadedFile::fake()->create('surat_kuasa.pdf', 150, 'application/pdf');

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
            'has_building' => 0,
            'akte_file' => $akteFile,
            'ktp_file' => $ktpFile,
            'surat_kuasa_file' => $suratKuasaFile,
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

    public function test_parse_pbg_document_endpoint(): void
    {
        $taxpayer = Taxpayer::factory()->create();
        Sanctum::actingAs($taxpayer, ['*']);

        $samplePdfPath = '/Users/pondokit/Herd/retribusi-mobile/public/templates/contoh_pbg_baubau.pdf';
        if (file_exists($samplePdfPath)) {
            $file = new UploadedFile($samplePdfPath, 'contoh_pbg_baubau.pdf', 'application/pdf', null, true);

            $response = $this->post('/api/citizen/pbb/parse-pbg-document', [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'data' => [
                        'document_type' => 'PBG',
                        'pbg_number' => 'PBG-747201-20092026-001',
                        'building_area' => 140,
                        'building_floors' => 2,
                    ],
                ]);
        }
    }

    public function test_admin_can_view_submitted_nop_applications_and_approve(): void
    {
        // 1. Citizen registers an application for someone else with PBG
        $citizen = Taxpayer::factory()->create([
            'nik' => '7472010101010001',
            'name' => 'Warga Kuasa',
            'address' => 'Jl. Kuasa No. 1, Baubau',
        ]);

        Sanctum::actingAs($citizen, ['*']);

        $akteFile = UploadedFile::fake()->create('akte.pdf', 150, 'application/pdf');
        $ktpFile = UploadedFile::fake()->create('ktp.pdf', 150, 'application/pdf');
        $suratKuasaFile = UploadedFile::fake()->create('surat_kuasa.pdf', 150, 'application/pdf');
        $pbgFile = UploadedFile::fake()->create('pbg.pdf', 150, 'application/pdf');

        $submitRes = $this->post('/api/citizen/pbb/nop-applications', [
            'nik' => $citizen->nik,
            'name' => $citizen->name,
            'address' => 'Jl. Diponegoro No. 88, Kel. Batupoaro',
            'land_area' => '250',
            'has_building' => 1,
            'building_area' => '120',
            'building_floors' => 2,
            'building_usage' => 'Rumah Tinggal',
            'pbg_number' => 'PBG-747201-20260920-0099',
            'pbg_date' => '2026-09-18',
            'registered_for' => 'other',
            'owner_nik' => '7472010101010009',
            'owner_name' => 'Haji Mansyur',
            'akte_file' => $akteFile,
            'ktp_file' => $ktpFile,
            'surat_kuasa_file' => $suratKuasaFile,
            'imb_file' => $pbgFile,
        ], [
            'Accept' => 'application/json',
        ]);

        $submitRes->assertStatus(201);
        $appId = $submitRes->json('data.id');

        // 2. Admin logs in and views incoming applications on admin endpoint
        $admin = User::factory()->create([
            'id' => 999,
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $adminListRes = $this->get('/api/pbb/bapenda/nop-applications', [
            'Accept' => 'application/json',
        ]);

        $adminListRes->assertStatus(200)
            ->assertJsonFragment([
                'id' => $appId,
                'name' => 'Warga Kuasa',
                'owner_name' => 'Haji Mansyur',
                'registered_for' => 'other',
                'building_area' => 120,
            ]);

        // Verify metadata has PBG number and owner nik
        $item = collect($adminListRes->json('data'))->firstWhere('id', $appId);
        $this->assertNotNull($item);
        $this->assertEquals('7472010101010009', $item['metadata']['owner_nik'] ?? null);
        $this->assertEquals('PBG-747201-20260920-0099', $item['metadata']['pbg_number'] ?? null);

        // 3. Admin approves and sets the official 18-digit NOP
        $officialNop = '747201000100100880';
        $approveRes = $this->post("/api/pbb/bapenda/nop-applications/{$appId}/status", [
            'status' => 'APPROVED',
            'nop' => $officialNop,
        ], [
            'Accept' => 'application/json',
        ]);

        $approveRes->assertStatus(200);

        $this->assertDatabaseHas('pbb_nop_applications', [
            'id' => $appId,
            'status' => 'APPROVED',
            'nop' => $officialNop,
        ]);

        // 4. Citizen checks history and sees the new NOP has been issued!
        Sanctum::actingAs($citizen, ['*']);
        $citizenHistoryRes = $this->get('/api/citizen/pbb/nop-applications', [
            'Accept' => 'application/json',
        ]);

        $citizenHistoryRes->assertStatus(200)
            ->assertJsonFragment([
                'id' => $appId,
                'status' => 'APPROVED',
                'nop' => $officialNop,
            ]);
    }
}
