<?php

namespace Tests\Feature;

use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\AssetRentalInspection;
use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetPuprFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Opd $pupr;
    protected User $petugas;
    protected User $opdAdmin;
    protected User $superAdmin;
    protected User $outsider;
    protected AssetItem $item;
    protected Taxpayer $taxpayer;
    protected RetributionType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pupr = Opd::factory()->create(['code' => 'PUPR', 'name' => 'Dinas PUPR']);

        $this->type = RetributionType::factory()->create([
            'opd_id' => $this->pupr->id,
            'name' => 'Sewa Alat Berat',
            'category' => 'Pemanfaatan Kekayaan Daerah',
        ]);

        RetributionClassification::factory()->create([
            'opd_id' => $this->pupr->id,
            'retribution_type_id' => $this->type->id,
            'name' => 'Denda Overtime Sewa Alat',
            'code' => 'DENDA-OVERTIME-SEWA-ALAT',
        ]);

        $this->petugas = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $this->pupr->id,
            'status' => 'active',
        ]);

        $this->opdAdmin = User::factory()->create([
            'role' => 'opd',
            'opd_id' => $this->pupr->id,
            'status' => 'active',
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->outsider = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => Opd::factory()->create(['code' => 'DPRD'])->id,
            'status' => 'active',
        ]);

        $this->item = AssetItem::factory()->alatBerat()->create([
            'opd_id' => $this->pupr->id,
            'tarif' => 350000,
            'satuan_tarif' => 'per jam',
            'status_operasional' => 'Tersedia',
        ]);

        $this->taxpayer = Taxpayer::factory()->create(['opd_id' => $this->pupr->id]);
    }

    protected function createRental(string $status = 'active', float $tarif = 350000, string $satuan = 'per jam', string $lama = '8'): AssetRental
    {
        return AssetRental::create([
            'rental_code' => 'SEWA-' . mt_rand(10000000, 99999999),
            'nomor_kontrak' => 'KONTRAK-TEST-001',
            'taxpayer_id' => $this->taxpayer->id,
            'user_id' => $this->opdAdmin->id,
            'asset_item_id' => $this->item->id,
            'opd_id' => $this->pupr->id,
            'retribution_type_id' => $this->type->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addDays(2)->toDateString(),
            'lama_sewa' => $lama,
            'satuan_sewa' => $satuan,
            'tarif_per_satuan' => $tarif,
            'total_biaya' => $tarif * (float) $lama,
            'dp' => 0,
            'sisa_pembayaran' => $tarif * (float) $lama,
            'status' => $status,
            'lokasi_penggunaan' => 'Jl. Uji Coba',
        ]);
    }

    protected function inspectionPayload(int $rentalId, float $hourMeter, string $type, array $overrides = []): array
    {
        return array_merge([
            'rental_id' => $rentalId,
            'hour_meter_value' => $hourMeter,
            'fuel_level' => 80,
            'checklist' => [
                'engine_oil' => 'GOOD',
                'hydraulic_system' => 'GOOD',
                'track_tires' => 'GOOD',
                'brakes_steering' => 'GOOD',
                'safety_cabin_k3' => 'GOOD',
            ],
            'condition_notes' => 'Unit sehat.',
            'inspector_gps_lat' => -5.4667,
            'inspector_gps_lng' => 122.6167,
        ], $overrides);
    }

    public function test_petugas_pupr_can_list_only_pupr_active_rentals(): void
    {
        $rental = $this->createRental('active');
        $this->createRental('pending_verification');

        $response = $this->actingAs($this->petugas)->getJson('/api/asset/rentals');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
        $this->assertSame($rental->rental_code, $response->json('data.0.rental_code'));
    }

    public function test_super_admin_can_see_all_rentals(): void
    {
        $this->createRental('active');

        $response = $this->actingAs($this->superAdmin)->getJson('/api/asset/rentals');

        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_outsider_opd_is_forbidden(): void
    {
        $this->createRental('active');

        $this->actingAs($this->outsider)->getJson('/api/asset/rentals')->assertStatus(403);
        $this->actingAs($this->outsider)->postJson('/api/pupr/inspection/pre', [
            'rental_id' => 1,
            'hour_meter_value' => 100,
            'checklist' => [
                'engine_oil' => 'GOOD',
                'hydraulic_system' => 'GOOD',
                'track_tires' => 'GOOD',
                'brakes_steering' => 'GOOD',
                'safety_cabin_k3' => 'GOOD',
            ],
        ])->assertStatus(403);
    }

    public function test_pre_inspection_activates_rental_and_records_hour_meter(): void
    {
        $rental = $this->createRental('pending_verification');

        $response = $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre', $this->inspectionPayload($rental->id, 1200.5, 'pre')
        );

        $response->assertCreated();
        $response->assertJsonPath('data.inspection.hour_meter', 1200.5);
        $response->assertJsonPath('data.inspection.inspection_type', 'pre_operation');
        $response->assertJsonPath('data.inspection.checklist.engine_oil', 'GOOD');

        $this->assertDatabaseHas('asset_rentals', ['id' => $rental->id, 'status' => 'active']);
        $this->assertSame(1200.5, (float) AssetRentalInspection::first()->hour_meter);
    }

    public function test_post_inspection_triggers_overtime_denda_bill(): void
    {
        $rental = $this->createRental('active');

        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre', $this->inspectionPayload($rental->id, 1000, 'pre')
        )->assertCreated();

        $response = $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/post', $this->inspectionPayload($rental->id, 1090, 'post')
        );

        $response->assertCreated();
        $response->assertJsonPath('data.overtime.is_overtime', true);
        $response->assertJsonPath('data.overtime.actual_hours', 90);
        $response->assertJsonPath('data.overtime.booked_hours', 8);
        $response->assertJsonPath('data.overtime.overtime_hours', 82);
        $response->assertJsonPath('data.overtime.overtime_rate', 350000);
        $response->assertJsonPath('data.overtime.overtime_amount', 28700000);

        $this->assertDatabaseHas('bills', [
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => $this->pupr->id,
            'status' => 'pending',
        ]);

        $bill = Bill::withoutGlobalScopes()->first();
        $this->assertSame(28700000.0, (float) $bill->amount);
        $this->assertSame('pupr_overtime', $bill->metadata['source']);
        $this->assertSame($rental->id, $bill->metadata['asset_rental_id']);
    }

    public function test_post_inspection_without_overtime_does_not_create_bill(): void
    {
        $rental = $this->createRental('active', 350000, 'per jam', '1');

        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre', $this->inspectionPayload($rental->id, 1000, 'pre')
        )->assertCreated();

        $response = $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/post', $this->inspectionPayload($rental->id, 1000.5, 'post')
        );

        $response->assertCreated();
        $response->assertJsonPath('data.overtime.is_overtime', false);
        $response->assertJsonPath('data.overtime.actual_hours', 0.5);
        $response->assertJsonPath('data.overtime.overtime_hours', 0);

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_overtime_bill_is_idempotent_no_duplicate(): void
    {
        $rental = $this->createRental('active');

        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre', $this->inspectionPayload($rental->id, 1000, 'pre')
        )->assertCreated();

        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/post', $this->inspectionPayload($rental->id, 1090, 'post')
        )->assertCreated();

        $second = $this->createRental('active');
        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre', $this->inspectionPayload($second->id, 1000, 'pre')
        )->assertCreated();
        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/post', $this->inspectionPayload($second->id, 1090, 'post')
        )->assertCreated();

        $this->assertDatabaseCount('bills', 2);
    }

    public function test_admin_can_manage_asset_items(): void
    {
        $response = $this->actingAs($this->opdAdmin)->postJson('/api/asset-items', [
            'opd_id' => $this->pupr->id,
            'name' => 'Grader G140',
            'code' => 'ALAT-GRADER-01',
            'category' => 'alat-berat',
            'tarif' => 400000,
            'satuan_tarif' => 'per jam',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('asset_items', ['code' => 'ALAT-GRADER-01']);

        $this->actingAs($this->opdAdmin)->getJson('/api/asset-items')->assertOk();
    }

    public function test_survey_kelayakan_submission(): void
    {
        $rental = $this->createRental('approved');

        $response = $this->actingAs($this->petugas)->postJson("/api/asset/rentals/{$rental->id}/survey", [
            'survey_akses_jalan' => true,
            'survey_dekat_jalan_raya' => true,
            'survey_keamanan' => true,
            'survey_lahan_luas' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.survey_kesimpulan', 'Lokasi layak & memenuhi standar operasional olah gerak alat berat.');
        $this->assertNotNull($rental->fresh()->survey_submitted_at);
    }

    public function test_admin_creates_rental_and_updates_status(): void
    {
        $response = $this->actingAs($this->opdAdmin)->postJson('/api/asset/rentals', [
            'asset_item_id' => $this->item->id,
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => $this->pupr->id,
            'tanggal_mulai' => now()->toDateString(),
            'lama_sewa' => '4',
            'satuan_sewa' => 'per jam',
            'tarif_per_satuan' => 350000,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'pending_verification');
        $response->assertJsonPath('data.total_biaya', 1400000);

        $rentalId = $response->json('data.id');

        $this->actingAs($this->opdAdmin)
            ->patchJson("/api/asset/rentals/{$rentalId}/status", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_generic_inspection_post_triggers_overtime_via_frontend_endpoint(): void
    {
        $rental = $this->createRental('active');

        $checklist = [
            'engine_oil' => 'GOOD',
            'hydraulic_system' => 'GOOD',
            'track_tires' => 'GOOD',
            'brakes_steering' => 'GOOD',
            'safety_cabin_k3' => 'GOOD',
        ];

        // Inspeksi pra via endpoint generik yang dipakai aplikasi petugas.
        $this->actingAs($this->petugas)->postJson("/api/asset/rentals/{$rental->id}/inspection", [
            'inspection_type' => 'pre_operation',
            'hour_meter_value' => 1000,
            'checklist' => $checklist,
            'inspector_gps_lat' => -5.4667,
            'inspector_gps_lng' => 122.6167,
        ])->assertCreated();

        // Inspeksi pasca via endpoint yang sama -> harus memicu penagihan overtime.
        $response = $this->actingAs($this->petugas)->postJson("/api/asset/rentals/{$rental->id}/inspection", [
            'inspection_type' => 'post_operation',
            'hour_meter_value' => 1090,
            'checklist' => $checklist,
            'inspector_gps_lat' => -5.4667,
            'inspector_gps_lng' => 122.6167,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('overtime.is_overtime', true);
        $response->assertJsonPath('overtime.actual_hours', 90);
        $response->assertJsonPath('overtime.overtime_hours', 82);
        $response->assertJsonPath('overtime.overtime_amount', 28700000);

        $this->assertDatabaseCount('bills', 1);

        $bill = Bill::withoutGlobalScopes()->first();
        $this->assertSame('pupr_overtime', $bill->metadata['source']);
        $this->assertSame($rental->id, $bill->metadata['asset_rental_id']);

        $this->assertSame(90.0, (float) $rental->fresh()->actual_hours);
        $this->assertTrue((bool) AssetRentalInspection::latest('id')->first()->is_overtime);
    }

    public function test_generic_inspection_post_without_overtime_does_not_create_bill(): void
    {
        $rental = $this->createRental('active', 350000, 'per jam', '1');

        $checklist = [
            'engine_oil' => 'GOOD',
            'hydraulic_system' => 'GOOD',
            'track_tires' => 'GOOD',
            'brakes_steering' => 'GOOD',
            'safety_cabin_k3' => 'GOOD',
        ];

        $this->actingAs($this->petugas)->postJson("/api/asset/rentals/{$rental->id}/inspection", [
            'inspection_type' => 'pre_operation',
            'hour_meter_value' => 1000,
            'checklist' => $checklist,
        ])->assertCreated();

        $response = $this->actingAs($this->petugas)->postJson("/api/asset/rentals/{$rental->id}/inspection", [
            'inspection_type' => 'post_operation',
            'hour_meter_value' => 1000.5,
            'checklist' => $checklist,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('overtime.is_overtime', false);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_admin_verify_approve_contract_complete_workflow(): void
    {
        $rental = $this->createRental('pending_verification');

        // Tahap 1: Verifikasi -> Diverifikasi
        $this->actingAs($this->opdAdmin)
            ->postJson("/api/asset/rentals/{$rental->id}/verify", ['catatan' => 'Dokumen lengkap.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.verified_by.id', $this->opdAdmin->id);

        $this->assertDatabaseHas('asset_rentals', ['id' => $rental->id, 'status' => 'verified', 'catatan_verifikator' => 'Dokumen lengkap.']);

        // Tahap 2: Approve Kepala UPTD -> Disetujui
        $this->actingAs($this->opdAdmin)
            ->postJson("/api/asset/rentals/{$rental->id}/approve", ['catatan' => 'Disetujui.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by.id', $this->opdAdmin->id);

        // Terbitkan kontrak & termin pembayaran
        $this->actingAs($this->opdAdmin)
            ->postJson("/api/asset/rentals/{$rental->id}/contract", [
                'nomor_kontrak' => '04/SPSP.7/LOADER/V/2026',
                'tahap_1_amount' => 1960000,
                'tahap_2_amount' => 840000,
            ])
            ->assertOk()
            ->assertJsonPath('data.nomor_kontrak', '04/SPSP.7/LOADER/V/2026');

        $this->assertDatabaseHas('asset_rentals', [
            'id' => $rental->id,
            'nomor_kontrak' => '04/SPSP.7/LOADER/V/2026',
            'tahap_1_amount' => 1960000,
            'tahap_2_amount' => 840000,
        ]);

        // Pencatatan pengembalian -> Selesai
        $this->actingAs($this->opdAdmin)
            ->postJson("/api/asset/rentals/{$rental->id}/complete", [
                'tanggal_pengembalian' => now()->toDateString(),
                'kondisi_pengembalian' => 'Baik',
                'actual_hours' => 90,
                'denda' => 500000,
                'catatan' => 'Unit kembali dalam kondisi baik.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.denda', 500000);

        $this->assertDatabaseHas('asset_rentals', [
            'id' => $rental->id,
            'status' => 'completed',
            'kondisi_pengembalian' => 'Baik',
            'actual_hours' => 90,
            'denda' => 500000,
        ]);
    }

    public function test_admin_workflow_rejects_invalid_transitions(): void
    {
        $rental = $this->createRental('pending_verification');

        // Approve sebelum verify harus ditolak.
        $this->actingAs($this->opdAdmin)
            ->postJson("/api/asset/rentals/{$rental->id}/approve")
            ->assertStatus(422);

        // Verify dari status selain Diajukan harus ditolak.
        $rental->update(['status' => 'approved']);
        $this->actingAs($this->opdAdmin)
            ->postJson("/api/asset/rentals/{$rental->id}/verify")
            ->assertStatus(422);

        // Petugas tidak boleh menjalankan alur admin.
        $pending = $this->createRental('pending_verification');
        $this->actingAs($this->petugas)
            ->postJson("/api/asset/rentals/{$pending->id}/verify")
            ->assertStatus(403);
    }

    public function test_photo_path_is_stored_on_pupr_pre_inspection(): void
    {
        $rental = $this->createRental('pending_verification');

        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre',
            $this->inspectionPayload($rental->id, 1000, 'pre', [
                'photo_path' => 'https://res.cloudinary.com/demo/retribusi/inspeksi-alat/pre001.jpg',
            ])
        )->assertCreated();

        $inspection = AssetRentalInspection::first();
        $this->assertSame('https://res.cloudinary.com/demo/retribusi/inspeksi-alat/pre001.jpg', $inspection->photo_path);
    }

    public function test_photo_path_is_stored_on_generic_inspection_endpoint(): void
    {
        $rental = $this->createRental('active');

        $this->actingAs($this->petugas)->postJson("/api/asset/rentals/{$rental->id}/inspection", [
            'inspection_type' => 'post_operation',
            'hour_meter_value' => 1005,
            'checklist' => ['engine_oil' => 'GOOD'],
            'photo_path' => 'https://res.cloudinary.com/demo/retribusi/inspeksi-alat/post001.jpg',
        ])->assertCreated();

        $inspection = AssetRentalInspection::latest()->first();
        $this->assertSame('https://res.cloudinary.com/demo/retribusi/inspeksi-alat/post001.jpg', $inspection->photo_path);
    }

    public function test_photo_path_optional_nullable(): void
    {
        $rental = $this->createRental('pending_verification');

        $this->actingAs($this->petugas)->postJson(
            '/api/pupr/inspection/pre',
            $this->inspectionPayload($rental->id, 1000, 'pre')
        )->assertCreated();

        $inspection = AssetRentalInspection::first();
        $this->assertNull($inspection->photo_path);
    }
}