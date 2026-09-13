<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\JukirWallet;
use App\Models\Opd;
use App\Models\ParkingLocation;
use App\Models\ParkingSession;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DishubQuadrupleLockAndJembatanBatuTest extends TestCase
{
    use RefreshDatabase;

    private Opd $dishub;
    private RetributionType $retType;
    private RetributionClassification $classification;
    private ParkingLocation $locationJembatanBatu;
    private User $jukir;
    private User $inspector;
    private User $admin;
    private User $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        config(['snap.briva.enabled' => false]);

        $this->dishub = Opd::factory()->create([
            'name' => 'Dinas Perhubungan',
            'code' => 'DISHUB',
        ]);

        $this->retType = RetributionType::factory()->create([
            'opd_id' => $this->dishub->id,
            'name' => 'Retribusi Pelayanan Parkir & Kepelabuhanan',
        ]);

        $this->classification = RetributionClassification::factory()->create([
            'opd_id' => $this->dishub->id,
            'retribution_type_id' => $this->retType->id,
            'name' => 'Kawasan Terpadu Jembatan Batu',
            'code' => 'RET-DISHUB-JB',
        ]);

        $this->locationJembatanBatu = ParkingLocation::create([
            'code' => 'PKR-JB-01',
            'name' => 'Pelataran & Dermaga Kawasan Jembatan Batu',
            'category' => 'retribusi_khusus',
            'opd_id' => $this->dishub->id,
            'classification_id' => $this->classification->id,
            'rate_r2' => 2000,
            'rate_r4' => 3000,
            'latitude' => -5.4625000,
            'longitude' => 122.6012000,
            'is_active' => true,
        ]);

        $this->jukir = User::factory()->create([
            'name' => 'La Ode Jukir Resmi',
            'role' => 'petugas',
            'opd_id' => $this->dishub->id,
        ]);

        $this->inspector = User::factory()->create([
            'name' => 'Inspektur Dishub / Satpol PP',
            'role' => 'petugas',
            'opd_id' => $this->dishub->id,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Dishub Baubau',
            'role' => 'admin',
            'opd_id' => $this->dishub->id,
        ]);

        $this->citizen = User::factory()->create([
            'name' => 'Warga Sadar Pajak',
            'role' => 'warga',
        ]);
    }

    // ── 1. JUKIR PROFILE & DEPOSIT TOP-UP ──

    public function test_jukir_can_view_profile_and_deposit_balance(): void
    {
        Sanctum::actingAs($this->jukir);

        $response = $this->getJson('/api/parking/jukir/profile');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'La Ode Jukir Resmi')
            ->assertJsonPath('data.split_rkud_percent', 70)
            ->assertJsonPath('data.split_jukir_percent', 30);
    }

    public function test_jukir_can_topup_deposit_balance(): void
    {
        Sanctum::actingAs($this->jukir);

        $response = $this->postJson('/api/parking/jukir/topup', [
            'amount' => 100000,
            'payment_method' => 'qris',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.topup_amount', 100000);

        $this->assertDatabaseHas('jukir_profiles', [
            'user_id' => $this->jukir->id,
        ]);
    }

    // ── 2. PREPAID CASH TRANSACTION & 70:30 SPLIT ──

    public function test_jukir_can_record_prepaid_cash_with_automatic_70_percent_rkud_deduction(): void
    {
        Sanctum::actingAs($this->jukir);

        // Pastikan shift terbuka
        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->locationJembatanBatu->id,
        ])->assertStatus(201);

        // Setup saldo deposit awal Rp 100.000
        DB::table('jukir_profiles')->updateOrInsert(
            ['user_id' => $this->jukir->id],
            [
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/0001',
                'surat_tugas_expired_at' => now()->addMonths(6),
                'deposit_balance' => 100000.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Transaksi Mobil (R4: Rp 3.000 -> RKUD 70% = Rp 2.100, Jukir 30% = Rp 900)
        $response = $this->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->locationJembatanBatu->id,
            'vehicle_type' => 'r4',
            'plate_hint' => 'DT 1234 BG',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.tariff_total', 3000)
            ->assertJsonPath('data.deposit_deducted_rkud', 2100)
            ->assertJsonPath('data.cash_kept_by_jukir', 3000)
            ->assertJsonPath('data.jukir_net_earnings', 900)
            ->assertJsonPath('data.remaining_deposit', 97900);

        // Verifikasi struk thermal 58mm mencantumkan watermark resmi
        $this->assertEquals('MINTA STRUK RESMI M-PAD. TANPA STRUK = PARKIR GRATIS!', $response->json('data.thermal_print_payload.footer_notice'));
        $this->assertNotEmpty($response->json('data.receipt_token'));

        // Verifikasi saldo di DB berkurang tepat 2100
        $profile = DB::table('jukir_profiles')->where('user_id', $this->jukir->id)->first();
        $this->assertEquals(97900.00, (float) $profile->deposit_balance);
    }

    public function test_prepaid_cash_fails_when_deposit_is_insufficient(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->locationJembatanBatu->id,
        ])->assertStatus(201);

        // Set deposit jukir tipis (hanya Rp 1.000, padahal untuk R4 butuh Rp 2.100)
        DB::table('jukir_profiles')->updateOrInsert(
            ['user_id' => $this->jukir->id],
            [
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/0001',
                'surat_tugas_expired_at' => now()->addMonths(6),
                'deposit_balance' => 1000.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $response = $this->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->locationJembatanBatu->id,
            'vehicle_type' => 'r4',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'INSUFFICIENT_DEPOSIT');
    }

    // ── 3. PROXY GT SHIP CATEGORIES & OVERNIGHT TRUCK PARKING ──

    public function test_prepaid_cash_with_proxy_gt_ship_category(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->locationJembatanBatu->id,
        ])->assertStatus(201);

        DB::table('jukir_profiles')->updateOrInsert(
            ['user_id' => $this->jukir->id],
            [
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/0001',
                'surat_tugas_expired_at' => now()->addMonths(6),
                'deposit_balance' => 50000.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Golongan I (< 5 GT): Rp 1.000 -> RKUD 70% = Rp 700
        $response = $this->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->locationJembatanBatu->id,
            'vehicle_type' => 'proxy_gt_1',
            'plate_hint' => 'KM BERKAH',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tariff_total', 1000)
            ->assertJsonPath('data.deposit_deducted_rkud', 700)
            ->assertJsonPath('data.thermal_print_payload.vehicle_type', 'KAPAL GOL I (< 5 GT)');

        // Verifikasi disclaimer kelaikan KSOP tercantum
        $this->assertStringContainsString('Bukan bukti kelaikan laut / SPB KSOP', $response->json('data.thermal_print_payload.legal_notice'));
    }

    public function test_prepaid_cash_with_proxy_gt_ship_multi_day(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->locationJembatanBatu->id,
        ])->assertStatus(201);

        DB::table('jukir_profiles')->updateOrInsert(
            ['user_id' => $this->jukir->id],
            [
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/0001',
                'surat_tugas_expired_at' => now()->addMonths(6),
                'deposit_balance' => 50000.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Golongan II (5 - 10 GT): Rp 3.000 / hari x 3 hari = Rp 9.000 -> RKUD 70% = Rp 6.300
        $response = $this->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->locationJembatanBatu->id,
            'vehicle_type' => 'proxy_gt_2',
            'duration_days' => 3,
            'plate_hint' => 'KM SPEED BAUBAU 01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tariff_total', 9000)
            ->assertJsonPath('data.deposit_deducted_rkud', 6300)
            ->assertJsonPath('data.thermal_print_payload.duration_days', 3)
            ->assertJsonPath('data.thermal_print_payload.vehicle_type', 'KAPAL GOL II (5 - 10 GT) (3 Hari)');

        $this->assertStringContainsString('Bukan bukti kelaikan laut / SPB KSOP', $response->json('data.thermal_print_payload.legal_notice'));
    }

    public function test_overnight_truck_parking_at_jembatan_batu(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->locationJembatanBatu->id,
        ])->assertStatus(201);

        DB::table('jukir_profiles')->updateOrInsert(
            ['user_id' => $this->jukir->id],
            [
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/0001',
                'surat_tugas_expired_at' => now()->addMonths(6),
                'deposit_balance' => 50000.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Penitipan Truk Inap (Pasal 88 Perda 1/2024 = Rp 25.000 -> RKUD 70% = Rp 17.500)
        $response = $this->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->locationJembatanBatu->id,
            'vehicle_type' => 'inap_truk',
            'plate_hint' => 'DT 9988 AU',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tariff_total', 25000)
            ->assertJsonPath('data.deposit_deducted_rkud', 17500)
            ->assertJsonPath('data.jukir_net_earnings', 7500);
    }

    // ── 4. INSPECTION SPOT-CHECK & SANCTIONS ──

    public function test_inspector_can_conduct_spot_check_and_detect_leakage(): void
    {
        Sanctum::actingAs($this->inspector);

        // Simulasi pemeriksaan: Terpantau 20 unit mobil fisik, tapi digital 0 -> critical leakage
        $response = $this->getJson("/api/parking/inspector/spot-check?parking_location_id={$this->locationJembatanBatu->id}&physical_r2=10&physical_r4=15");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.audit_result.risk_level', 'critical')
            ->assertJsonPath('data.audit_result.discrepancy_units', 25);
    }

    public function test_inspector_can_issue_sanction_to_violating_jukir(): void
    {
        Sanctum::actingAs($this->inspector);

        DB::table('jukir_profiles')->updateOrInsert(
            ['user_id' => $this->jukir->id],
            [
                'surat_tugas_no' => 'ST.DISHUB/PKR/2026/0001',
                'surat_tugas_expired_at' => now()->addMonths(6),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Pembekuan 7 hari (SP2)
        $response = $this->postJson('/api/parking/inspector/sanction', [
            'jukir_user_id' => $this->jukir->id,
            'sanction_type' => 'sp2_freeze_7days',
            'reason' => 'Menerima pembayaran tunai tanpa input aplikasi dan tidak mencetak struk thermal.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.new_status', 'suspended');

        $profile = DB::table('jukir_profiles')->where('user_id', $this->jukir->id)->first();
        $this->assertEquals('suspended', $profile->status);
    }

    // ── 5. GIS HEATMAP & EWS ANOMALIES ──

    public function test_admin_can_view_gis_heatmap_and_ews_anomalies(): void
    {
        Sanctum::actingAs($this->admin);

        // Heatmap
        $heatmapRes = $this->getJson('/api/parking/admin/gis-heatmap');
        $heatmapRes->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['summary', 'spots']]);

        // EWS Anomalies
        $anomaliesRes = $this->getJson('/api/parking/admin/anomalies');
        $anomaliesRes->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['total_alerts', 'alerts']]);
    }

    // ── 6. CITIZEN GAMIFICATION (CLAIM RECEIPT) ──

    public function test_citizen_can_claim_receipt_token_for_lottery_points(): void
    {
        Sanctum::actingAs($this->citizen);

        $response = $this->postJson('/api/parking/citizen/claim-receipt', [
            'receipt_token' => 'PKR-260908-ABC123',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.points_earned', 10)
            ->assertJsonStructure(['data' => ['lottery_ticket_number', 'benefit']]);
    }

    // ── 7. PROXY GT RATES MASTER ──

    public function test_proxy_gt_rates_returns_perda_matrix(): void
    {
        Sanctum::actingAs($this->jukir);

        $response = $this->getJson('/api/parking/harbor/proxy-gt-rates');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(4, 'data')
            ->assertJsonFragment(['code' => 'proxy_gt_1', 'rate' => 1000])
            ->assertJsonFragment(['code' => 'proxy_gt_2', 'rate' => 3000])
            ->assertJsonFragment(['code' => 'proxy_gt_3', 'rate' => 5000])
            ->assertJsonFragment(['code' => 'proxy_gt_4', 'rate' => 10000]);
    }

    // ── 8. KIOS JEMBATAN BATU RECURRING AUTO-BILLING ──

    public function test_kios_jembatan_batu_can_list_15_kios_status(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/parking/kios-jembatan-batu');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_kios', 15)
            ->assertJsonPath('data.monthly_rate_per_kios', 120000)
            ->assertJsonPath('data.total_monthly_potential', 1800000)
            ->assertJsonCount(15, 'data.kios_list');
    }

    public function test_kios_jembatan_batu_can_generate_monthly_bills_idempotently(): void
    {
        Sanctum::actingAs($this->admin);

        $period = '2026-09';

        // 1. Eksekusi pertama: 15 tagihan baru diterbitkan
        $response1 = $this->postJson('/api/parking/kios-jembatan-batu/generate-bills', [
            'period' => $period,
        ]);

        $response1->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.generated_count', 15)
            ->assertJsonPath('data.skipped_count', 0)
            ->assertJsonPath('data.total_billed_amount', 1800000);

        // Verifikasi 15 Bills tercipta di DB
        $billsCount = Bill::where('period', $period)
            ->where('metadata->source', 'dishub_kios_recurring')
            ->count();
        $this->assertEquals(15, $billsCount);

        // 2. Eksekusi kedua (idempotent): 0 diterbitkan, 15 dilewati
        $response2 = $this->postJson('/api/parking/kios-jembatan-batu/generate-bills', [
            'period' => $period,
        ]);

        $response2->assertStatus(201)
            ->assertJsonPath('data.generated_count', 0)
            ->assertJsonPath('data.skipped_count', 15);
    }
}
