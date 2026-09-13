<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PuprInspectionAndDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected Opd $opd;
    protected User $adminPupr;
    protected User $inspector;
    protected Taxpayer $taxpayer;
    protected AssetItem $asset;
    protected AssetRental $rental;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opd = Opd::factory()->create([
            'name' => 'Dinas PUPR Kota Baubau',
        ]);

        $this->adminPupr = User::factory()->create([
            'role' => 'admin',
            'opd_id' => $this->opd->id,
        ]);

        $this->inspector = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $this->opd->id,
            'name' => 'La Ode Juru Ukur',
        ]);

        $this->taxpayer = Taxpayer::factory()->create([
            'opd_id' => $this->opd->id,
            'name' => 'PT Baubau Konstruksi Mandiri',
            'nik' => '7472012345670001',
            'phone' => '081234567890',
        ]);

        $category = AssetCategory::firstOrCreate(
            ['slug' => 'alat-berat'],
            ['name' => 'Alat Berat', 'is_active' => true]
        );

        $type = RetributionType::firstOrCreate(
            ['code' => 'PKD-PUPR'],
            [
                'opd_id' => $this->opd->id,
                'name' => 'Pemanfaatan Kekayaan Daerah',
                'category' => 'Jasa Usaha',
                'is_active' => true,
            ]
        );

        $classification = RetributionClassification::firstOrCreate(
            ['code' => 'AB-EXCAVATOR'],
            [
                'opd_id' => $this->opd->id,
                'retribution_type_id' => $type->id,
                'name' => 'Excavator PC-200',
                'rate' => 330000,
            ]
        );

        $this->asset = AssetItem::create([
            'opd_id' => $this->opd->id,
            'category_id' => $category->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'name' => 'Excavator Komatsu PC-200',
            'code' => 'EXC-01',
            'satuan_tarif' => 'per jam',
            'tarif' => 330000,
            'wajib_tronton' => true,
            'status_operasional' => 'Operasional',
            'is_active' => true,
        ]);

        $this->rental = AssetRental::create([
            'rental_code' => 'AST-2026-TEST-01',
            'nomor_kontrak' => '027/KTR/PUPR/2026',
            'taxpayer_id' => $this->taxpayer->id,
            'asset_item_id' => $this->asset->id,
            'retribution_type_id' => $type->id,
            'retribution_classification_id' => $classification->id,
            'tanggal_mulai' => Carbon::now()->addDays(2),
            'tanggal_selesai' => Carbon::now()->addDays(5),
            'lama_sewa' => 24,
            'satuan_sewa' => 'jam',
            'tarif_per_satuan' => 330000,
            'total_biaya' => 7920000,
            'status' => 'Disetujui',
            'lokasi_penggunaan' => 'Proyek Drainase Murhum',
            'include_tronton' => true,
            'jarak_tronton_km' => 15,
            'biaya_tronton' => 450000,
            'approved_by_user_id' => $this->adminPupr->id,
        ]);
    }

    public function test_inspector_can_submit_pre_operation_inspection(): void
    {
        $payload = [
            'inspection_type' => 'pre_operation',
            'hour_meter_value' => 1250.5,
            'checklist' => [
                'engine_oil' => 'GOOD',
                'hydraulic_system' => 'GOOD',
                'track_tension' => 'GOOD',
                'brake_system' => 'GOOD',
                'safety_cabin' => 'GOOD',
            ],
            'damage_notes' => 'Baret halus pada counterweight belakang, fungsi normal.',
            'inspector_gps_lat' => -5.4633,
            'inspector_gps_lng' => 122.6012,
        ];

        $response = $this->actingAs($this->inspector)
            ->postJson("/api/asset/rentals/{$this->rental->id}/inspection", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'rental_id' => $this->rental->id,
                    'inspection' => [
                        'type' => 'pre_operation',
                        'hour_meter' => 1250.5,
                    ],
                ],
            ]);

        $this->rental->refresh();
        $this->assertNotNull($this->rental->metadata);
        $this->assertCount(1, $this->rental->metadata['inspections']);
    }

    public function test_inspector_can_submit_post_operation_inspection_and_auto_calculate_actual_hours(): void
    {
        // 1. Catat pra-operasi
        $this->actingAs($this->inspector)
            ->postJson("/api/asset/rentals/{$this->rental->id}/inspection", [
                'inspection_type' => 'pre_operation',
                'hour_meter_value' => 1250.0,
            ]);

        // 2. Catat pasca-operasi
        $postResponse = $this->actingAs($this->inspector)
            ->postJson("/api/asset/rentals/{$this->rental->id}/inspection", [
                'inspection_type' => 'post_operation',
                'hour_meter_value' => 1272.5, // Selisih = 22.5 jam
                'damage_notes' => 'Pekerjaan selesai, alat dibersihkan.',
            ]);

        $postResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'actual_hours' => 22.5,
                ],
            ]);

        $this->rental->refresh();
        $this->assertEquals(22.5, (float) $this->rental->actual_hours);
        $this->assertCount(2, $this->rental->metadata['inspections']);
    }

    public function test_can_generate_esipab_document_data(): void
    {
        $response = $this->actingAs($this->adminPupr)
            ->getJson("/api/asset/rentals/{$this->rental->id}/document/sipab");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'document_type' => 'e-SIPAB',
                'title' => 'Surat Izin Pemakaian Alat Berat',
                'data' => [
                    'rental_code' => $this->rental->rental_code,
                    'nomor_kontrak' => $this->rental->nomor_kontrak,
                    'pemohon' => [
                        'nama' => 'PT Baubau Konstruksi Mandiri',
                    ],
                    'alat' => [
                        'nama' => 'Excavator Komatsu PC-200',
                    ],
                    'pejabat_tte' => [
                        'tte_status' => 'TTE_VERIFIED_BSRE',
                    ],
                ],
            ]);

        $this->assertStringContainsString('mpad.baubaukota.go.id/verify/sipab/', $response->json('data.qr_verification_url'));
    }

    public function test_can_generate_ebast_document_data(): void
    {
        $response = $this->actingAs($this->adminPupr)
            ->getJson("/api/asset/rentals/{$this->rental->id}/document/bast");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'document_type' => 'e-BAST',
                'title' => 'Berita Acara Serah Terima Pemakaian Alat Berat',
                'data' => [
                    'rental_code' => $this->rental->rental_code,
                    'pihak_kedua' => [
                        'nama' => 'PT Baubau Konstruksi Mandiri',
                    ],
                    'alat' => [
                        'nama' => 'Excavator Komatsu PC-200',
                    ],
                ],
            ]);

        $this->assertStringContainsString('mpad.baubaukota.go.id/verify/bast/', $response->json('data.qr_verification_url'));
    }
}
