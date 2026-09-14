<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\ParkingDeposit;
use App\Models\ParkingLocation;
use App\Models\ParkingShift;
use App\Models\RetributionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingQuickCashierTest extends TestCase
{
    use RefreshDatabase;

    protected User $jukir;

    protected User $inspector;

    protected ParkingLocation $location;

    protected ParkingLocation $otherLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $opd = Opd::factory()->create(['code' => 'DISHUB']);
        $type = RetributionType::factory()->create(['opd_id' => $opd->id, 'category' => 'parkir']);

        $this->jukir = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $opd->id,
            'status' => 'active',
            'surat_tugas_no' => 'ST.TEST/2026/001',
            'surat_tugas_expired_at' => now()->addMonths(6),
        ]);

        $this->inspector = User::factory()->create([
            'role' => 'opd',
            'opd_id' => $opd->id,
            'status' => 'active',
        ]);

        $this->location = ParkingLocation::create([
            'code' => 'JL-TST',
            'name' => 'Parkir Uji Coba',
            'category' => 'retribusi_umum',
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'rate_r2' => 1500,
            'rate_r4' => 2000,
            'is_active' => true,
        ]);

        $this->otherLocation = ParkingLocation::create([
            'code' => 'JL-TST-2',
            'name' => 'Parkir Uji Coba Dua',
            'category' => 'retribusi_umum',
            'opd_id' => $opd->id,
            'retribution_type_id' => $type->id,
            'rate_r2' => 1000,
            'rate_r4' => 1500,
            'is_active' => true,
        ]);
    }

    protected function openShift(): void
    {
        $this->actingAs($this->jukir)
            ->postJson('/api/parking/shift/open', ['parking_location_id' => $this->location->id])
            ->assertCreated()
            ->assertJsonPath('data.wallet.shift_status', 'open');
    }

    protected function topup(float $amount = 50000): void
    {
        $this->actingAs($this->jukir)
            ->postJson('/api/parking/jukir/topup', ['amount' => $amount, 'payment_method' => 'qris'])
            ->assertOk()
            ->assertJsonPath('data.balance_after', $amount);
    }

    public function test_jukir_opens_shift_and_records_server_priced_qris_session(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->jukir)->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r4',
            'payment_method' => 'qris',
            'qris_reference' => 'QR-TST-001',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.session.payment_method', 'qris');
        $response->assertJsonPath('data.session.amount', 2000);
        $response->assertJsonPath('data.session.shift_id', ParkingShift::where('user_id', $this->jukir->id)->firstOrFail()->id);

        $this->assertDatabaseCount('parking_sessions', 1);
        $this->assertDatabaseHas('parking_deposits', ['user_id' => $this->jukir->id, 'type' => 'topup']);
    }

    public function test_topup_then_prepaid_cash_applies_seventy_thirty_quadruple_lock(): void
    {
        $this->openShift();
        $this->topup();

        $response = $this->actingAs($this->jukir)->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.tariff_total', 1500);
        $response->assertJsonPath('data.deposit_deducted_rkud', 1050);
        $response->assertJsonPath('data.cash_kept_by_jukir', 450);
        $response->assertJsonPath('data.jukir_net_earnings', 450);
        $response->assertJsonPath('data.remaining_deposit', 48950);
        $response->assertJsonPath('data.session.payment_method', 'cash');
        $response->assertJsonPath('shift_summary.total_cash', 1500);
        $response->assertJsonPath('shift_summary.total_sessions', 1);
        $this->assertStringStartsWith('MPD-PRK-', $response->json('data.receipt_token'));
        $this->assertArrayHasKey('thermal_print_payload', $response->json('data'));

        $this->assertDatabaseHas('parking_deposits', [
            'user_id' => $this->jukir->id,
            'type' => 'deduction',
            'amount' => -1050,
        ]);
        $this->assertSame(48950.0, ParkingDeposit::balanceFor($this->jukir->id));
    }

    public function test_prepaid_cash_is_rejected_when_rkud_deposit_insufficient(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->jukir)->postJson('/api/parking/sessions/prepaid-cash', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'INSUFFICIENT_DEPOSIT');
        $response->assertJsonPath('required_rkud', 1050);

        $this->assertDatabaseCount('parking_sessions', 0);
    }

    public function test_session_is_rejected_without_open_shift_at_same_location(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->jukir)->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->otherLocation->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'qris',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('parking_sessions', 0);
    }

    public function test_shift_summary_returns_flat_wallet_and_summary(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->jukir)->getJson('/api/parking/shift-summary');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('wallet.shift_status', 'open');
        $response->assertJsonPath('wallet.shift_date', now()->toDateString());
        $response->assertJsonStructure([
            'success',
            'message',
            'wallet' => ['shift_status', 'total_cash', 'total_qris', 'total_sessions'],
            'sessions',
            'summary' => ['total_sessions', 'total_cash', 'total_qris'],
        ]);
    }

    public function test_close_shift_locks_shift_as_settled(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->jukir)->postJson('/api/parking/shift/close');

        $response->assertOk();
        $response->assertJsonPath('data.wallet.shift_status', 'closed');
        $response->assertJsonPath('data.wallet.settled', true);

        $this->assertDatabaseHas('parking_shifts', [
            'user_id' => $this->jukir->id,
            'shift_date' => now()->toDateString(),
            'shift_status' => 'closed',
        ]);
    }

    public function test_jukir_profile_reports_shift_and_assigned_location(): void
    {
        $this->openShift();

        $response = $this->actingAs($this->jukir)->getJson('/api/parking/jukir/profile');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonPath('data.shift_status', 'open');
        $response->assertJsonPath('data.assigned_location.id', $this->location->id);
    }

    public function test_jukir_cannot_access_inspector_endpoints(): void
    {
        $this->actingAs($this->jukir)
            ->getJson('/api/parking/inspector/spot-check?parking_location_id=' . $this->location->id)
            ->assertStatus(403);

        $this->actingAs($this->jukir)
            ->postJson('/api/parking/inspector/sanction', [
                'jukir_user_id' => $this->jukir->id,
                'sanction_type' => 'sp1_warning',
            ])
            ->assertStatus(403);
    }

    public function test_inspector_cannot_operate_as_jukir(): void
    {
        $this->actingAs($this->inspector)
            ->postJson('/api/parking/shift/open', ['parking_location_id' => $this->location->id])
            ->assertStatus(403);

        $this->actingAs($this->inspector)
            ->postJson('/api/parking/sessions/prepaid-cash', [
                'parking_location_id' => $this->location->id,
                'vehicle_type' => 'r2',
            ])
            ->assertStatus(403);
    }

    public function test_petugas_outsider_opd_is_forbidden(): void
    {
        $outsiderOpd = Opd::factory()->create(['code' => 'DPRD']);
        $outsider = User::factory()->create(['role' => 'petugas', 'opd_id' => $outsiderOpd->id]);

        $this->actingAs($outsider)->getJson('/api/parking/locations')->assertStatus(403);
        $this->actingAs($outsider)->getJson('/api/parking/jukir/profile')->assertStatus(403);
    }

    public function test_inspector_spot_check_reports_physical_digital_discrepancy(): void
    {
        $this->openShift();

        $this->actingAs($this->jukir)->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'qris',
        ])->assertCreated();

        $this->actingAs($this->jukir)->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'qris',
        ])->assertCreated();

        $response = $this->actingAs($this->inspector)
            ->getJson('/api/parking/inspector/spot-check?parking_location_id=' . $this->location->id . '&physical_r2=10&physical_r4=2');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.digital_active_count.r2', 2);
        $response->assertJsonPath('data.physical_observed_count.total', 12);
        $response->assertJsonPath('data.audit_result.discrepancy_units', 10);
    }

    public function test_inspector_dashboard_aggregates_locations(): void
    {
        $response = $this->actingAs($this->inspector)->getJson('/api/parking/dashboard');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.total_locations', 2);
    }

    public function test_inspector_sp3_revokes_surat_tugas_and_suspends_jukir(): void
    {
        $response = $this->actingAs($this->inspector)->postJson('/api/parking/inspector/sanction', [
            'jukir_user_id' => $this->jukir->id,
            'sanction_type' => 'sp3_revoke_st',
            'reason' => 'Uji feature test',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sanction.sanction_type', 'sp3_revoke_st');
        $response->assertJsonPath('data.sanction.inspector_user_id', $this->inspector->id);
        $response->assertJsonPath('data.jukir_status', 'suspended');

        $this->assertDatabaseHas('parking_sanctions', [
            'jukir_user_id' => $this->jukir->id,
            'sanction_type' => 'sp3_revoke_st',
            'status' => 'applied',
        ]);
        $this->assertDatabaseHas('users', ['id' => $this->jukir->id, 'status' => 'suspended']);
    }
}