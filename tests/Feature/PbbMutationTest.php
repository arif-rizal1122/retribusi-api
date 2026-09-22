<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Taxpayer;
use App\Models\PbbMutation;
use App\Models\PbbSplitItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PbbMutationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $citizenUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.pbb@baubaukota.go.id',
        ]);

        $this->citizenUser = User::factory()->create([
            'role' => 'warga',
            'email' => 'warga.test@baubaukota.go.id',
        ]);
    }

    public function test_calculate_proportional_debt_success()
    {
        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->getJson('/api/pbb/calculator/proportional-debt?parent_land_area=1000&split_land_area=250&total_debt=1000000');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'parent_land_area' => 1000,
                    'split_land_area' => 250,
                    'remaining_land_area' => 750,
                    'total_parent_debt' => 1000000,
                    'proportional_debt' => 250000,
                    'remaining_parent_debt' => 750000,
                ],
            ]);
    }

    public function test_calculate_proportional_debt_rejects_exceeding_area()
    {
        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->getJson('/api/pbb/calculator/proportional-debt?parent_land_area=500&split_land_area=600&total_debt=1000000');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_scenario_1_split_kavling_submission_and_approval()
    {
        // 1. Submit Split Kavling
        $payload = [
            'parent_nop' => '74.72.020.007.001-0010.0',
            'applicant_nik' => '7472010101900001',
            'applicant_name' => 'La Ode Developer',
            'parent_land_area' => 1000,
            'total_parent_debt' => 2000000,
            'split_items' => [
                [
                    'target_nik' => '7472010101900002',
                    'target_name' => 'Wa Ode Pembeli 1',
                    'land_area_m2' => 300,
                ],
                [
                    'target_nik' => '7472010101900003',
                    'target_name' => 'La Ode Pembeli 2',
                    'land_area_m2' => 400,
                ],
            ],
            'notes' => 'Pecah kavling perumahan Katobengke',
        ];

        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->postJson('/api/pbb/mutations/split-kavling', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $mutationId = $response->json('data.id');
        $this->assertDatabaseHas('pbb_mutations', [
            'id' => $mutationId,
            'mutation_type' => 'SPLIT_KAVLING',
            'status' => 'PENDING',
        ]);
        $this->assertDatabaseCount('pbb_split_items', 2);

        // 2. Admin L1 (Ibu Yani) verify
        $resL1 = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/pbb/mutations/{$mutationId}/status", [
                'status' => 'VERIFIED_L1',
                'notes' => 'Kelengkapan berkas fisik & poligon GIS sesuai',
            ]);
        $resL1->assertStatus(200);

        // 3. Admin L2 (Ibu Salma) pre-approve
        $resL2 = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/pbb/mutations/{$mutationId}/status", [
                'status' => 'PRE_APPROVED_L2',
                'notes' => 'ZNT dan analisis piutang proporsional valid',
            ]);
        $resL2->assertStatus(200);

        // 4. Admin L3 (Ibu Ochi) approve final
        $resL3 = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/pbb/mutations/{$mutationId}/status", [
                'status' => 'APPROVED',
                'notes' => 'SK Pemecahan NOP disahkan',
            ]);
        $resL3->assertStatus(200);

        // Verify split items now have generated NOP
        $splitItems = PbbSplitItem::where('pbb_mutation_id', $mutationId)->get();
        foreach ($splitItems as $item) {
            $this->assertNotNull($item->generated_nop);
            $this->assertEquals('APPROVED', $item->status);
        }
    }

    public function test_scenario_4_rectification_submission()
    {
        $payload = [
            'parent_nop' => '74.72.010.001.001-0005.0',
            'applicant_nik' => '7472010101900004',
            'applicant_name' => 'La Ode Korban Typo',
            'correction_field' => 'NAMA_WP',
            'old_data' => ['nama_wp' => 'La Ode Korban Typo Salah'],
            'new_data' => ['nama_wp' => 'La Ode Korban Typo'],
            'notes' => 'Pembetulan ejaan nama sesuai KTP',
        ];

        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->postJson('/api/pbb/mutations/rectification', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mutation_type' => 'RECTIFICATION',
                    'status' => 'PENDING',
                ],
            ]);
    }

    public function test_scenario_5_transfer_ownership_submission()
    {
        $payload = [
            'parent_nop' => '74.72.030.001.001-0020.0',
            'applicant_nik' => '7472010101900005',
            'applicant_name' => 'Pembeli Baru',
            'previous_taxpayer_nik' => '7472010101900099',
            'previous_taxpayer_name' => 'Pemilik Lama',
            'new_taxpayer_nik' => '7472010101900005',
            'new_taxpayer_name' => 'Pembeli Baru',
            'bphtb_ntpd_ref' => 'NTPD-BPHTB-2026-999',
            'notes' => 'Peralihan hak jual beli dengan bukti NTPD lunas',
        ];

        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->postJson('/api/pbb/mutations/transfer-ownership', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mutation_type' => 'TRANSFER_OWNERSHIP',
                    'status' => 'PENDING',
                ],
            ]);
    }

    public function test_scenario_6_amalgamation_submission()
    {
        $payload = [
            'primary_target_nop' => '74.72.020.001.001-0001.0',
            'applicant_nik' => '7472010101900006',
            'applicant_name' => 'Pemilik Lahan Luas',
            'merged_source_nops' => [
                '74.72.020.001.001-0002.0',
                '74.72.020.001.001-0003.0',
            ],
            'notes' => 'Penggabungan 3 bidang tanah bersertifikat menjadi satu',
        ];

        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->postJson('/api/pbb/mutations/amalgamation', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mutation_type' => 'AMALGAMATION',
                    'status' => 'PENDING',
                ],
            ]);
    }

    public function test_scenario_7_cancellation_submission()
    {
        $payload = [
            'duplicate_nop_target' => '74.72.020.007.001-9999.0',
            'applicant_nik' => '7472010101900007',
            'applicant_name' => 'Warga Lapor Ganda',
            'cancellation_reason_code' => 'DUPLICATE',
            'notes' => 'NOP ganda terbit dua kali pada bidang tanah yang sama',
        ];

        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->postJson('/api/pbb/mutations/cancellation', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mutation_type' => 'CANCELLATION',
                    'status' => 'PENDING',
                ],
            ]);
    }

    public function test_bphtb_paid_webhook_auto_transfer()
    {
        $payload = [
            'nop' => '74.72.010.001.001-0077.0',
            'ntpd' => 'NTPD-BPHTB-AUTO-2026',
            'new_owner_nik' => '7472010101900088',
            'new_owner_name' => 'Wa Ode Notaris Klien',
        ];

        $response = $this->postJson('/api/pbb/callbacks/bphtb-paid', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'mutation_type' => 'TRANSFER_OWNERSHIP',
                    'status' => 'APPROVED',
                ],
            ]);
    }
}
