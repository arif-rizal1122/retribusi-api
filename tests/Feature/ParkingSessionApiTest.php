<?php

namespace Tests\Feature;

use App\Models\JukirWallet;
use App\Models\Opd;
use App\Models\ParkingLocation;
use App\Models\ParkingSession;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParkingSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private Opd $dishub;
    private RetributionType $retType;
    private RetributionClassification $classification;
    private ParkingLocation $location;
    private User $jukir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['snap.briva.enabled' => false]);

        $this->dishub = Opd::factory()->create(['name' => 'Dinas Perhubungan']);
        $this->retType = RetributionType::factory()->create([
            'opd_id' => $this->dishub->id,
        ]);
        $this->classification = RetributionClassification::factory()->create([
            'opd_id' => $this->dishub->id,
            'retribution_type_id' => $this->retType->id,
            'name' => 'Retribusi Parkir Tepi Jalan Umum',
            'code' => 'RET-PRK',
        ]);

        $this->location = ParkingLocation::create([
            'code' => 'PKR-SUDIRMAN-01',
            'name' => 'Ruas Jl. Jend. Sudirman Segmen A',
            'category' => 'retribusi_umum',
            'opd_id' => $this->dishub->id,
            'classification_id' => $this->classification->id,
            'rate_r2' => 2000,
            'rate_r4' => 3000,
        ]);

        $this->jukir = User::factory()->create([
            'role' => 'petugas',
            'opd_id' => $this->dishub->id,
        ]);
    }

    // ── Shift Management ──

    public function test_jukir_can_open_shift(): void
    {
        Sanctum::actingAs($this->jukir);

        $response = $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['shift_status' => 'open']);

        $this->assertDatabaseHas('jukir_wallets', [
            'user_id' => $this->jukir->id,
            'shift_status' => 'open',
        ]);
    }

    public function test_jukir_cannot_open_two_shifts_same_day(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ])->assertStatus(201);

        // Attempt second open — should return existing
        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ])->assertOk()->assertJsonFragment(['shift_status' => 'open']);
    }

    public function test_jukir_can_close_shift(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);

        $response = $this->postJson('/api/parking/shift/close');
        $response->assertOk()
            ->assertJsonPath('data.total_sessions', 0);

        $this->assertDatabaseHas('jukir_wallets', [
            'user_id' => $this->jukir->id,
            'shift_status' => 'closed',
        ]);
    }

    public function test_close_without_open_returns_422(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/close')
            ->assertStatus(422);
    }

    // ── Session Recording ──

    public function test_jukir_can_record_r2_cash_session(): void
    {
        Sanctum::actingAs($this->jukir);

        // Open shift first
        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);

        $response = $this->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Transaksi parkir berhasil dicatat.']);

        // Verify session created
        $this->assertDatabaseHas('parking_sessions', [
            'jukir_user_id' => $this->jukir->id,
            'vehicle_type' => 'r2',
            'amount' => 2000,
            'payment_method' => 'cash',
        ]);

        // Verify bill auto-generated
        $session = ParkingSession::latest()->first();
        $this->assertNotNull($session->bill_id);

        // Verify wallet updated
        $wallet = JukirWallet::forUser($this->jukir->id)->first();
        $this->assertEquals(2000, (float) $wallet->total_cash);
        $this->assertEquals(1, $wallet->total_sessions);
    }

    public function test_jukir_can_record_r4_qris_session(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);

        $response = $this->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r4',
            'payment_method' => 'qris',
            'qris_reference' => '08-0001',
            'plate_hint' => 'DU19',
        ]);

        $response->assertStatus(201);

        $wallet = JukirWallet::forUser($this->jukir->id)->first();
        $this->assertEquals(3000, (float) $wallet->total_qris);
        $this->assertEquals(0, (float) $wallet->total_cash);
        $this->assertEquals(1, $wallet->total_sessions);
    }

    public function test_recording_without_open_shift_returns_422(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'cash',
        ])->assertStatus(422)
          ->assertJsonFragment(['message' => 'Anda belum membuka shift hari ini. Silakan buka shift terlebih dahulu.']);
    }

    public function test_multiple_sessions_accumulate_correctly(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);

        // 3x R2 Cash = 6000
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/parking/sessions', [
                'parking_location_id' => $this->location->id,
                'vehicle_type' => 'r2',
                'payment_method' => 'cash',
            ])->assertStatus(201);
        }

        // 2x R4 QRIS = 6000
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/parking/sessions', [
                'parking_location_id' => $this->location->id,
                'vehicle_type' => 'r4',
                'payment_method' => 'qris',
                'qris_reference' => '08-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
            ])->assertStatus(201);
        }

        $wallet = JukirWallet::forUser($this->jukir->id)->first();
        $this->assertEquals(6000, (float) $wallet->total_cash);
        $this->assertEquals(6000, (float) $wallet->total_qris);
        $this->assertEquals(5, $wallet->total_sessions);
    }

    // ── Shift Summary ──

    public function test_shift_summary_returns_correct_data(): void
    {
        Sanctum::actingAs($this->jukir);

        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);

        $this->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'cash',
        ]);

        $response = $this->getJson('/api/parking/shift-summary');
        $response->assertOk()
            ->assertJsonPath('data.summary.total_sessions', 1)
            ->assertJsonPath('data.summary.shift_status', 'open');
    }

    // ── Locations ──

    public function test_jukir_can_list_parking_locations(): void
    {
        Sanctum::actingAs($this->jukir);

        $response = $this->getJson('/api/parking/locations');
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['code' => 'PKR-SUDIRMAN-01']);
    }

    // ── Dashboard ──

    public function test_dashboard_returns_aggregate(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        Sanctum::actingAs($admin);

        // Setup: jukir opens shift and records 2 sessions
        Sanctum::actingAs($this->jukir);
        $this->postJson('/api/parking/shift/open', [
            'parking_location_id' => $this->location->id,
        ]);
        $this->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r2',
            'payment_method' => 'cash',
        ]);
        $this->postJson('/api/parking/sessions', [
            'parking_location_id' => $this->location->id,
            'vehicle_type' => 'r4',
            'payment_method' => 'qris',
            'qris_reference' => '08-0001',
        ]);

        // Switch to admin
        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/parking/dashboard');
        $response->assertOk()
            ->assertJsonPath('data.active_shifts', 1)
            ->assertJsonPath('data.total_sessions', 2);
    }

    // ── Guest Guard ──

    public function test_guest_cannot_access_parking_endpoints(): void
    {
        $this->getJson('/api/parking/locations')->assertUnauthorized();
        $this->postJson('/api/parking/sessions')->assertUnauthorized();
        $this->getJson('/api/parking/shift-summary')->assertUnauthorized();
        $this->postJson('/api/parking/shift/open')->assertUnauthorized();
        $this->postJson('/api/parking/shift/close')->assertUnauthorized();
        $this->getJson('/api/parking/dashboard')->assertUnauthorized();
    }
}
