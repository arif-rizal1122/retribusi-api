<?php

namespace Tests\Feature;

use App\Models\DlhLabOrder;
use App\Models\Taxpayer;
use App\Models\User;
use Database\Seeders\DlhMasterTariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitizenDlhLabOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private Taxpayer $citizen;

    private Taxpayer $otherCitizen;

    private User $analyst;

    private int $parameterId;

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
            'name' => 'Retribusi Persampahan & Kebersihan',
            'code' => 'RET-SMP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(DlhMasterTariffSeeder::class);
        $this->citizen = Taxpayer::factory()->create(['opd_id' => 4]);
        $this->otherCitizen = Taxpayer::factory()->create(['opd_id' => 4]);
        $this->analyst = User::factory()->create(['role' => 'opd', 'opd_id' => 4]);
        $this->parameterId = (int) DB::table('dlh_lab_parameters')->where('code', 'LAB-PH')->value('id');
    }

    private function orderPayload(): array
    {
        return [
            'applicant_name' => 'Nama kontak layanan',
            'institution_name' => 'Usaha Uji Citizen',
            'contact_phone' => '081234567890',
            'applicant_address' => 'Baubau',
            'sample_matrix' => 'AIR_BERSIH',
            'sampling_location' => 'Lokasi uji',
            'sampling_date' => now()->addDays(3)->toDateString(),
            'parameter_ids' => [$this->parameterId],
        ];
    }

    private function createCitizenOrder(): DlhLabOrder
    {
        Sanctum::actingAs($this->citizen, ['*']);

        return DlhLabOrder::findOrFail($this->postJson('/api/v1/dlh/lab/orders', $this->orderPayload())
            ->assertCreated()->json('data.order.id'));
    }

    public function test_order_is_bound_to_authenticated_taxpayer(): void
    {
        $order = $this->createCitizenOrder();

        $this->assertSame($this->citizen->id, $order->taxpayer_id);
        $this->assertDatabaseHas('dlh_lab_orders', [
            'id' => $order->id,
            'taxpayer_id' => $this->citizen->id,
        ]);
    }

    public function test_citizen_cannot_read_or_bill_another_citizens_order(): void
    {
        $order = $this->createCitizenOrder();
        Sanctum::actingAs($this->otherCitizen, ['*']);

        $this->getJson("/api/v1/dlh/lab/orders/{$order->id}")->assertNotFound();
        $this->getJson("/api/v1/dlh/lab/orders/by-number/{$order->order_number}")->assertNotFound();
        $this->postJson("/api/v1/dlh/lab/orders/{$order->id}/billing")
            ->assertNotFound();
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_citizen_can_track_own_order_by_number(): void
    {
        $order = $this->createCitizenOrder();

        $this->getJson("/api/v1/dlh/lab/orders/by-number/{$order->order_number}")
            ->assertOk()
            ->assertJsonPath('data.order.id', $order->id)
            ->assertJsonPath('data.order.taxpayer_id', $this->citizen->id)
            ->assertJsonPath('data.parameters.0.code', 'LAB-PH');
    }

    public function test_citizen_cannot_upload_lab_results(): void
    {
        $order = $this->createCitizenOrder();

        $this->postJson("/api/v1/dlh/lab/orders/{$order->id}/results", [
            'results' => [[
                'parameter_id' => $this->parameterId,
                'result_value' => '7.2',
                'compliance_status' => 'COMPLIANT',
            ]],
        ])->assertForbidden();

        $this->assertDatabaseHas('dlh_lab_orders', [
            'id' => $order->id,
            'status' => 'DRAFT',
        ]);
    }

    public function test_internal_analyst_can_process_citizen_order(): void
    {
        $order = $this->createCitizenOrder();
        Sanctum::actingAs($this->analyst, ['*']);

        $this->getJson("/api/v1/dlh/lab/orders/{$order->id}")->assertOk();
        $this->postJson("/api/v1/dlh/lab/orders/{$order->id}/results", [
            'results' => [[
                'parameter_id' => $this->parameterId,
                'result_value' => '7.2',
                'compliance_status' => 'COMPLIANT',
            ]],
        ])->assertOk();
        $this->assertDatabaseHas('dlh_lab_orders', [
            'id' => $order->id,
            'status' => 'COMPLETED',
        ]);
    }
}
