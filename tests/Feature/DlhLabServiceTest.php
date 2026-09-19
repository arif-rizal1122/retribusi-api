<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DlhMasterTariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DlhLabServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $analyst;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('opds')->updateOrInsert(['id' => 4], [
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('retribution_types')->updateOrInsert(['id' => 17], [
            'opd_id' => 4,
            'name' => 'Retribusi Pelayanan Kebersihan & Lingkungan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('retribution_classifications')->updateOrInsert(['id' => 198], [
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'name' => 'Pelayanan Persampahan / Kebersihan',
            'code' => 'RET-SMP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed master parameter DLH Lab
        $this->seed(DlhMasterTariffSeeder::class);

        $this->analyst = User::create([
            'name' => 'Analis Lab DLH',
            'email' => 'analis.lab@baubaukota.go.id',
            'password' => bcrypt('password'),
            'role' => 'opd',
            'opd_id' => 4,
        ]);
    }

    public function test_get_lab_parameters_returns_active_parameters(): void
    {
        Sanctum::actingAs($this->analyst, ['*']);

        $response = $this->getJson('/api/v1/dlh/lab/parameters');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(7, 'data'); // 7 parameter Perda 1/2024
    }

    public function test_create_lab_order_calculates_total_amount(): void
    {
        Sanctum::actingAs($this->analyst, ['*']);

        // Ambil parameter COD (Rp 60.000) dan BOD (Rp 65.000)
        $paramCod = DB::table('dlh_lab_parameters')->where('code', 'LAB-COD')->first();
        $paramBod = DB::table('dlh_lab_parameters')->where('code', 'LAB-BOD')->first();

        $response = $this->postJson('/api/v1/dlh/lab/orders', [
            'applicant_name' => 'PT Baubau Sejahtera',
            'institution_name' => 'Klinik Medika Bahari',
            'contact_phone' => '08114000123',
            'applicant_address' => 'Jl. Betoambari No. 10',
            'sample_matrix' => 'AIR_LIMBAH',
            'sampling_location' => 'Outlet IPAL Klinik',
            'sampling_date' => '2026-09-10',
            'parameter_ids' => [$paramCod->id, $paramBod->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order.total_amount', 175000); // 85.000 + 90.000

        $orderId = $response->json('data.order.id');

        $this->assertDatabaseHas('dlh_lab_order_parameters', [
            'lab_order_id' => $orderId,
            'parameter_id' => $paramCod->id,
            'tariff_snapshot' => 85000,
        ]);
    }

    public function test_create_lab_order_rejects_duplicate_parameters(): void
    {
        Sanctum::actingAs($this->analyst, ['*']);
        $parameterId = (int) DB::table('dlh_lab_parameters')->where('code', 'LAB-PH')->value('id');

        $this->postJson('/api/v1/dlh/lab/orders', [
            'applicant_name' => 'Duplikat Parameter',
            'contact_phone' => '0812345678',
            'sample_matrix' => 'AIR_BERSIH',
            'sampling_date' => '2026-09-11',
            'parameter_ids' => [$parameterId, $parameterId],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'parameter_ids.0',
            'parameter_ids.1',
        ]);

        $this->assertDatabaseCount('dlh_lab_orders', 0);
    }

    public function test_create_lab_order_rejects_inactive_parameters(): void
    {
        Sanctum::actingAs($this->analyst, ['*']);
        $parameterId = (int) DB::table('dlh_lab_parameters')->where('code', 'LAB-PH')->value('id');
        DB::table('dlh_lab_parameters')->where('id', $parameterId)->update(['is_active' => false]);

        $this->postJson('/api/v1/dlh/lab/orders', [
            'applicant_name' => 'Parameter Nonaktif',
            'contact_phone' => '0812345678',
            'sample_matrix' => 'AIR_BERSIH',
            'sampling_date' => '2026-09-11',
            'parameter_ids' => [$parameterId],
        ])->assertUnprocessable()->assertJsonValidationErrors('parameter_ids');

        $this->assertDatabaseCount('dlh_lab_orders', 0);
    }

    public function test_generate_billing_skrd_and_record_results(): void
    {
        Sanctum::actingAs($this->analyst, ['*']);

        $paramPh = DB::table('dlh_lab_parameters')->where('code', 'LAB-PH')->first();

        // 1. Buat Order
        $orderRes = $this->postJson('/api/v1/dlh/lab/orders', [
            'applicant_name' => 'Hotel Murhum',
            'contact_phone' => '0812345678',
            'sample_matrix' => 'AIR_BERSIH',
            'sampling_date' => '2026-09-11',
            'parameter_ids' => [$paramPh->id],
        ]);

        $orderId = $orderRes->json('data.order.id');
        $orderNumber = $orderRes->json('data.order.order_number');

        // 2. Terbitkan Billing SKRD
        $billingRes = $this->postJson("/api/v1/dlh/lab/orders/{$orderId}/billing");

        $billingRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', 30000) // Tarif pH Rp 30.000 (Perda 1/2024)
            ->assertJsonPath('data.opd_id', 4)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_label', 'Menunggu pembayaran')
            ->assertJsonPath('data.can_pay', true);

        $billId = $billingRes->json('data.id');
        DB::table('bills')->where('id', $billId)->update(['status' => 'PAID']);

        $this->getJson("/api/v1/dlh/lab/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.bill.status', 'paid')
            ->assertJsonPath('data.bill.status_label', 'Lunas')
            ->assertJsonPath('data.bill.can_pay', false);

        $this->assertDatabaseHas('dlh_lab_orders', [
            'id' => $orderId,
            'status' => 'BILLING_ISSUED',
        ]);

        // 3. Analis Input Hasil Uji
        $resultRes = $this->postJson("/api/v1/dlh/lab/orders/{$orderId}/results", [
            'results' => [
                [
                    'parameter_id' => $paramPh->id,
                    'result_value' => '7.2',
                    'compliance_status' => 'COMPLIANT',
                ],
            ],
            'lhu_document_url' => 'https://storage.baubaukota.go.id/lhu/sample-01.pdf',
        ]);

        $resultRes->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('dlh_lab_orders', [
            'id' => $orderId,
            'status' => 'COMPLETED',
            'tte_status' => 'PENDING',
        ]);

        // 4. Verifikasi Publik LHU (Tanpa Login / Public Scan QR)
        $publicRes = $this->getJson("/api/verify/dlh-lab/{$orderNumber}");

        $publicRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_number', $orderNumber)
            ->assertJsonPath('data.parameters.0.result_value', '7.2')
            ->assertJsonPath('data.parameters.0.compliance_status', 'COMPLIANT');
    }
}
