<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HistoricalErrorRegressionTest
 *
 * Test regresi otomatis untuk SELURUH error historis yang pernah terjadi
 * di ekosistem SIPANDA. Setiap test case di-mapping ke satu error spesifik
 * dari register sejarah error (error_history_and_mitigation_registry.md).
 *
 * Kategori Error:
 *   1. Infrastruktur & Environment (CORS, Login, Migration)
 *   2. Logika Backend (Zone 500, Delete Rate 500, Formula Parser)
 *   3. Hardening Controller (Verification, Payment, MonthlyReport, PenaltyWaiver)
 */
class HistoricalErrorRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $opdAdmin;
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

        $this->opdAdmin = User::create([
            'name' => 'OPD Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'opd',
            'opd_id' => $this->opd->id
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
    // ERROR #1: Local Login 401 - Credential Mismatch
    // Akar Masalah: Akun demo di frontend tidak ada di database.
    // Mitigasi: Pastikan login mengembalikan 200+token untuk akun valid.
    // ========================================================================

    /** @test */
    public function error_login_returns_token_for_valid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    /** @test */
    public function error_login_returns_401_for_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    // ========================================================================
    // ERROR #2: Logout 401 - Token mismatch / already deleted
    // Akar Masalah: Token sudah dihapus tapi user mencoba logout lagi.
    // Mitigasi: Endpoint harus menerima logout tanpa crash.
    // ========================================================================

    /** @test */
    public function error_logout_succeeds_for_authenticated_user()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200);
    }

    /** @test */
    public function error_logout_returns_401_for_unauthenticated()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    // ========================================================================
    // ERROR #3: Zone Creation 500 - opd_id required tapi Super Admin tidak kirim
    // Akar Masalah: Validasi 'required|exists:opds,id' untuk opd_id.
    // Mitigasi: opd_id nullable + Auto-Inference dari retribution_type.
    // ========================================================================

    /** @test */
    public function error_zone_creation_without_opd_id_infers_from_type()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/zones', [
                'retribution_type_id' => $this->retributionType->id,
                'name' => 'Zona Tanpa OPD',
                'geometry_type' => 'point',
                'latitude' => -5.46,
                'longitude' => 122.60,
                // opd_id intentionally OMITTED
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('zones', [
            'name' => 'Zona Tanpa OPD',
            'opd_id' => $this->opd->id, // Auto-inferred!
        ]);
    }

    /** @test */
    public function error_zone_creation_auto_generates_code()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/zones', [
                'retribution_type_id' => $this->retributionType->id,
                'name' => 'Zona Auto Code',
                // code intentionally OMITTED
            ]);

        $response->assertStatus(201);
        $zone = Zone::where('name', 'Zona Auto Code')->first();
        $this->assertNotNull($zone);
        $this->assertNotEmpty($zone->code);
    }

    /** @test */
    public function error_zone_creation_returns_422_not_500_for_missing_required()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/zones', [
                'name' => 'Zona Without Type', // Missing retribution_type_id
            ]);

        $response->assertStatus(422); // NOT 500!
    }

    // ========================================================================
    // ERROR #4: Delete Rate 500 - Undefined variable $request
    // Akar Masalah: Method destroy() pakai $request->user() tapi parameter
    //              Request $request belum dideklarasi.
    // Mitigasi: Pastikan delete tidak crash (tidak 500).
    // ========================================================================

    /** @test */
    public function error_delete_rate_does_not_crash_with_500()
    {
        $rate = RetributionRate::create([
            'retribution_type_id' => $this->retributionType->id,
            'retribution_classification_id' => $this->classification->id,
            'opd_id' => $this->opd->id,
            'name' => 'Tarif Test Delete',
            'amount' => 25000,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->deleteJson("/api/retribution-rates/{$rate->id}");

        // Must NOT be 500 (the old bug). Should be 200 or 204.
        $this->assertNotEquals(500, $response->getStatusCode(),
            'Delete rate returned 500! The $request parameter bug may have regressed.');
        $response->assertSuccessful();
    }

    /** @test */
    public function error_delete_nonexistent_rate_returns_404_not_500()
    {
        $response = $this->actingAs($this->superAdmin)
            ->deleteJson('/api/retribution-rates/99999');

        $response->assertStatus(404); // NOT 500
    }

    // ========================================================================
    // ERROR #5: Classification creation - opd_id inference
    // Akar Masalah: opd_id required tapi bisa di-infer dari type.
    // ========================================================================

    /** @test */
    public function error_classification_infers_opd_id_from_type()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/retribution-classifications', [
                'retribution_type_id' => $this->retributionType->id,
                'name' => 'Kelas B - Komersil',
                'code' => 'KLS-B',
                // opd_id intentionally OMITTED
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('retribution_classifications', [
            'name' => 'Kelas B - Komersil',
            'opd_id' => $this->opd->id, // Auto-inferred
        ]);
    }

    // ========================================================================
    // ERROR #6: Verification store - crash tanpa Throwable catch
    // Mitigasi: Harus dikembalikan JSON error, bukan HTML exception.
    // ========================================================================

    /** @test */
    public function error_verification_store_returns_422_for_invalid_data()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/verifications', [
                // All required fields missing
            ]);

        $response->assertStatus(422); // Validation error, NOT 500
    }

    // ========================================================================
    // ERROR #7: Payment store - crash tanpa Throwable catch
    // Mitigasi: Harus dikembalikan JSON error yang jelas.
    // ========================================================================

    /** @test */
    public function error_payment_store_returns_422_for_missing_fields()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/payments', [
                // All required fields missing
            ]);

        $response->assertStatus(422); // Validation error, NOT 500
    }

    /** @test */
    public function error_payment_store_returns_422_for_invalid_tax_object()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/payments', [
                'tax_object_id' => 99999, // Non-existent
                'billing_period' => 'Januari 2026',
                'payment_method' => 'cash',
                'amount' => 50000,
            ]);

        $response->assertStatus(422); // Validation error on exists rule
    }

    // ========================================================================
    // ERROR #8: Formula Parser 500 - Variabel tidak ditemukan
    // Mitigasi: Parser harus menangani gracefully tanpa crash.
    // ========================================================================

    /** @test */
    public function error_formula_parser_handles_missing_variables()
    {
        $response = $this->postJson('/api/simulate-tax', [
            'calculation_formula' => 'tarif * volume',
            'variables' => [
                'tarif' => 10000,
                // 'volume' intentionally MISSING
            ],
        ]);

        // Should not be 500 — either 200 with result 0 or 422 with error
        $this->assertNotEquals(500, $response->getStatusCode(),
            'Formula parser crashed! Missing variable handling may have regressed.');
    }

    // ========================================================================
    // ERROR #9: Migration Mismatch - Column not found di prod
    // Mitigasi: Test bahwa semua tabel kritis ada dengan kolom yang benar.
    // ========================================================================

    /** @test */
    public function error_migration_zones_table_has_required_columns()
    {
        $this->assertTrue(
            \Schema::hasColumns('zones', ['name', 'code', 'opd_id', 'retribution_type_id']),
            'Tabel zones kehilangan kolom kritis! Pastikan migrasi sudah dijalankan.'
        );
    }

    /** @test */
    public function error_migration_retribution_rates_has_required_columns()
    {
        $this->assertTrue(
            \Schema::hasColumns('retribution_rates', ['name', 'amount', 'retribution_type_id']),
            'Tabel retribution_rates kehilangan kolom kritis!'
        );
    }

    /** @test */
    public function error_migration_bills_table_has_required_columns()
    {
        $this->assertTrue(
            \Schema::hasColumns('bills', ['bill_number', 'amount', 'status', 'taxpayer_id']),
            'Tabel bills kehilangan kolom kritis!'
        );
    }

    // ========================================================================
    // ERROR #10: Hardening - Semua endpoint kritis harus mengembalikan JSON
    //            bukan HTML (error pages). Test bahwa response = application/json.
    // ========================================================================

    /** @test */
    public function error_all_api_responses_are_json_not_html()
    {
        $endpoints = [
            ['POST', '/api/zones', ['name' => 'test']],
            ['POST', '/api/retribution-classifications', ['name' => 'test']],
            ['POST', '/api/payments', ['amount' => 100]],
            ['POST', '/api/verifications', ['type' => 'test']],
        ];

        foreach ($endpoints as [$method, $url, $data]) {
            $response = $this->actingAs($this->superAdmin)
                ->json($method, $url, $data);

            $this->assertTrue(
                str_contains($response->headers->get('Content-Type', ''), 'json'),
                "Endpoint {$method} {$url} returned non-JSON response! Status: {$response->getStatusCode()}"
            );
        }
    }

    // ========================================================================
    // ERROR #11: CORS - Test bahwa /api/login bisa diakses tanpa auth
    //            (Public endpoint harus terbuka)
    // ========================================================================

    /** @test */
    public function error_public_endpoints_are_accessible_without_auth()
    {
        // Login endpoint
        $response = $this->postJson('/api/login', [
            'email' => 'any@test.com',
            'password' => 'any'
        ]);
        $this->assertNotEquals(500, $response->getStatusCode(),
            'Public /api/login endpoint crashed!');

        // PBB inquiry endpoint (with Http::fake to avoid real network call)
        Http::fake([
            '*' => Http::response(['status' => 200, 'token' => 'mock'], 200),
        ]);
        $response = $this->postJson('/api/pbb/bapenda/inquiry', [
            'nop' => '123456789012345678',
            'tahun' => '2026'
        ]);
        $this->assertNotEquals(500, $response->getStatusCode(),
            'Public /api/pbb/bapenda/inquiry endpoint crashed!');

        // OPD list
        $response = $this->getJson('/api/opds');
        $response->assertSuccessful();
    }

    // ========================================================================
    // ERROR #12: Empty state - Pastikan endpoint list mengembalikan data
    //            kosong dengan benar, tanpa crash.
    // ========================================================================

    /** @test */
    public function error_empty_list_endpoints_return_valid_json()
    {
        // Zones - empty
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/zones');
        $response->assertSuccessful();

        // Rates - empty
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/retribution-rates');
        $response->assertSuccessful();

        // Classifications - empty already has one from setUp, but test it still works
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/retribution-classifications');
        $response->assertSuccessful();

        // Verifications - empty
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/verifications');
        $response->assertSuccessful();

        // Bills - empty
        $response = $this->actingAs($this->superAdmin)
            ->getJson('/api/bills');
        $response->assertSuccessful();
    }

    // ========================================================================
    // ERROR #13: PBB Bapenda API fail - Login gagal ke server eksternal
    // (Sudah di-cover oleh PbbBapendaTest.php, ini tambahan regression)
    // ========================================================================

    /** @test */
    public function error_pbb_inquiry_validation_rejects_bad_input()
    {
        $response = $this->postJson('/api/pbb/bapenda/inquiry', [
            'nop' => 'terlalu-pendek',
            'tahun' => 'bukan-tahun'
        ]);

        $response->assertStatus(422);
    }

    // ========================================================================
    // ERROR #14: Penalty Waiver - crash tanpa Throwable catch
    // ========================================================================

    /** @test */
    public function error_amnesty_store_returns_422_for_missing_fields()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/amnesty', [
                // All required fields missing
            ]);

        $response->assertStatus(422); // NOT 500
    }

    /** @test */
    public function error_amnesty_store_returns_422_for_invalid_bill()
    {
        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/amnesty', [
                'bill_id' => 99999, // Non-existent
                'reason' => 'Test reason',
                'reduction_type' => 'percentage',
                'reduction_value' => 50,
            ]);

        $response->assertStatus(422); // Validation error on exists rule
    }
}
