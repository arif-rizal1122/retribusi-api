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

    public function test_certificate_and_ktp_parser_and_bpn_lookup(): void
    {
        $taxpayer = Taxpayer::factory()->create();
        Sanctum::actingAs($taxpayer, ['*']);

        // 1. Test BPN Lookup with Local Mapping
        \App\Models\NibNopMapping::create([
            'nib' => '2105010100999',
            'nop' => '747201000100100999',
            'taxpayer_name' => 'Pemilik Tanah Sah',
            'land_area_m2' => 350.00,
            'znt' => 'AA',
            'match_status' => 'matched',
            'source' => 'bhumi',
        ]);

        $bpnRes = $this->get('/api/citizen/pbb/bpn-lookup?nib=2105010100999', [
            'Accept' => 'application/json',
        ]);

        $bpnRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'is_matched' => true,
                    'nib' => '2105010100999',
                    'nop' => '747201000100100999',
                    'land_area' => 350,
                    'znt' => 'AA',
                ],
            ]);

        // 2. Test Certificate Parser Service unit extraction
        $certParser = app(\App\Services\PbbCertificateParserService::class);
        $sampleText = "KEMENTERIAN AGRARIA DAN TATA RUANG / BPN\nSERTIPIKAT HAK MILIK No. 04512\nNIB: 21.05.01.01.00999\nKelurahan Katobengke Kecamatan Betoambari\nSurat Ukur Luas 350 M2\nNama Pemegang Hak: La Ode Hasan";
        $extracted = $certParser->extractAttributesFromText($sampleText);

        $this->assertEquals('SHM', $extracted['certificate_type']);
        $this->assertEquals('04512', $extracted['certificate_number']);
        $this->assertEquals('2105010100999', $extracted['nib']);
        $this->assertEquals(350, $extracted['land_area']);
        $this->assertEquals('Betoambari', $extracted['district']);
        $this->assertEquals('Katobengke', $extracted['sub_district']);
        $this->assertEquals('La Ode Hasan', $extracted['owner_name']);

        // 3. Test KTP Parser Service unit extraction
        $ktpParser = app(\App\Services\PbbKtpParserService::class);
        $sampleKtpText = "PROVINSI SULAWESI TENGGARA\nKOTA BAUBAU\nNIK : 7472011508850002\nNama : WA ODE SITI\nAlamat : JL. GAJAH MADA NO. 12\nRT/RW : 002/001\nKel/Desa : BATARAGURU\nKecamatan : WOLIO";
        $extractedKtp = $ktpParser->extractAttributesFromText($sampleKtpText);

        $this->assertEquals('7472011508850002', $extractedKtp['nik']);
        $this->assertEquals('Wa Ode Siti', $extractedKtp['name']);
        $this->assertEquals('Jl. Gajah Mada No. 12', $extractedKtp['address']);
        $this->assertEquals('Wolio', $extractedKtp['district']);
    }
}

