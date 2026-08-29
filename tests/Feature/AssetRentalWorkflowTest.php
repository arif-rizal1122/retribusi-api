<?php

namespace Tests\Feature;

use App\Models\AssetCategory;
use App\Models\AssetItem;
use App\Models\AssetRental;
use App\Models\Opd;
use App\Models\Taxpayer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetRentalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Taxpayer $taxpayer;
    protected Opd $opd;
    protected AssetCategory $category;
    protected AssetItem $asset;
    protected User $adminPupr;
    protected User $surveyor;

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

        $this->surveyor = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $this->opd->id,
        ]);

        $this->taxpayer = Taxpayer::factory()->create([
            'opd_id' => $this->opd->id,
            'name' => 'Jeksen Duma',
            'nik' => '7326133012950001',
        ]);

        $this->category = AssetCategory::create([
            'name' => 'Alat Berat',
            'slug' => 'alat-berat',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $retributionType = \App\Models\RetributionType::create([
            'opd_id' => $this->opd->id,
            'name' => 'Retribusi Jasa Usaha',
            'category' => 'Jasa Usaha',
            'base_amount' => 0,
            'is_active' => true,
        ]);

        \App\Models\RetributionClassification::create([
            'opd_id' => $this->opd->id,
            'retribution_type_id' => $retributionType->id,
            'name' => 'Pemanfaatan Aset Daerah',
            'code' => 'JU-ASET',
            'is_active' => true,
        ]);

        $this->asset = AssetItem::create([
            'category_id' => $this->category->id,
            'opd_id' => $this->opd->id,
            'name' => 'Wheel Loader Komatsu WA200-5',
            'code' => 'AB-003',
            'merk_type' => 'Komatsu WA200-5',
            'kondisi' => 'Baik',
            'status_operasional' => 'Operasional',
            'tarif_default' => 250000,
            'satuan_tarif' => 'per jam',
            'buffer_default' => 1,
            'is_active' => true,
            'is_demo' => false,
        ]);
    }

    public function test_full_asset_rental_workflow_with_survey_and_contract()
    {
        $today = Carbon::today();
        $start = $today->copy()->addDays(5)->toDateString();
        $end = $today->copy()->addDays(19)->toDateString(); // 14 hari pemakaian

        // 1. Warga mengajukan sewa
        $response = $this->actingAs($this->taxpayer)->postJson('/api/citizen/asset/rentals', [
            'asset_item_id' => $this->asset->id,
            'tanggal_mulai' => $start,
            'tanggal_selesai' => $end,
            'satuan_sewa' => 'per jam',
            'tarif_per_satuan' => 250000,
            'metode_pembayaran' => 'Transfer Kasda Bank Sultra',
            'lokasi_penggunaan' => 'Bandara Betoambari Baubau',
            'jenis_pekerjaan' => 'Menggusur Material',
            'nama_proyek' => 'Perluasan Runway Bandara',
        ]);

        $response->assertStatus(201);
        $rentalId = $response->json('data.id');
        $this->assertNotNull($rentalId);

        $rental = AssetRental::find($rentalId);
        $this->assertEquals('Diajukan', $rental->status);

        // 2. Surveyor submit 4-point survey kelayakan lapangan
        $surveyRes = $this->actingAs($this->surveyor)->postJson("/api/asset/rentals/{$rentalId}/survey", [
            'survey_akses_jalan' => true,
            'survey_dekat_jalan_raya' => true,
            'survey_keamanan' => true,
            'survey_lahan_luas' => true,
            'survey_kesimpulan' => 'Akses jalan memadai dan lokasi aman untuk olah gerak unit Komatsu WA200.',
            'survey_foto_path' => 'surveys/betoambari_loader.jpg',
        ]);

        $surveyRes->assertStatus(200);
        $surveyRes->assertJsonPath('data.survey_akses_jalan', true);

        // 3. Verifikator memverifikasi
        $verifyRes = $this->actingAs($this->adminPupr)->postJson("/api/asset/rentals/{$rentalId}/verify", [
            'catatan' => 'Dokumen KTP dan hasil survey lapangan 4 butir memenuhi syarat.',
        ]);
        $verifyRes->assertStatus(200);
        $this->assertEquals('Diverifikasi', AssetRental::find($rentalId)->status);

        // 4. Admin menerbitkan kontrak & termin pembayaran (Tahap 1 DP, Tahap 2 Pelunasan HM)
        $contractRes = $this->actingAs($this->adminPupr)->postJson("/api/asset/rentals/{$rentalId}/contract", [
            'nomor_kontrak' => '04/SPSR.7/LOADER/V/2026',
            'tahap_1_amount' => 18375000,
            'tahap_2_amount' => 8125000,
        ]);
        $contractRes->assertStatus(200);
        $this->assertEquals('04/SPSR.7/LOADER/V/2026', AssetRental::find($rentalId)->nomor_kontrak);

        // 5. Approval Kepala UPTD
        $approveRes = $this->actingAs($this->adminPupr)->postJson("/api/asset/rentals/{$rentalId}/approve", [
            'catatan' => 'Disetujui. Silakan lakukan pembayaran Tahap 1 ke Bank Sultra PAD.',
        ]);
        $approveRes->assertStatus(200);
        $this->assertEquals('Disetujui', AssetRental::find($rentalId)->status);

        // 6. Pencatatan Pengembalian Unit + Actual Hours (106 Jam)
        $completeRes = $this->actingAs($this->adminPupr)->postJson("/api/asset/rentals/{$rentalId}/complete", [
            'tanggal_pengembalian' => $end,
            'kondisi_pengembalian' => 'Baik',
            'catatan' => 'Unit selesai digunakan dalam kondisi prima.',
            'actual_hours' => 106,
        ]);
        $completeRes->assertStatus(200);
        $finalRental = AssetRental::find($rentalId);
        $this->assertEquals('Selesai', $finalRental->status);
        $this->assertEquals('106.00', $finalRental->actual_hours);
    }
}
