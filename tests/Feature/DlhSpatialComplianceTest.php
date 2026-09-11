<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DlhSpatialComplianceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Taxpayer $taxpayer;

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

        $this->taxpayer = Taxpayer::create([
            'opd_id' => 4,
            'nik' => '7472010101900099',
            'name' => 'Warga Sampah Spasial',
            'phone' => '081234567890',
            'address' => 'Jl. Wolter Monginsidi No. 12',
        ]);

        $this->admin = User::create([
            'name' => 'Kepala Dinas DLH',
            'email' => 'kadis.dlh@baubaukota.go.id',
            'password' => bcrypt('password'),
            'role' => 'opd',
            'opd_id' => 4,
        ]);
    }

    public function test_compliance_map_geojson_returns_proper_markers(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $currentPeriod = Carbon::now()->format('Y-m');

        // Persil 1: Lunas (Hijau #10B981)
        $objPaid = TaxObject::create([
            'nop' => '74.72.010.001.001-0101.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Persil Lunas',
            'address' => 'Jl. Pahlawan No. 1',
            'district' => 'Betoambari',
            'sub_district' => 'Katobengke',
            'latitude' => -5.46512300,
            'longitude' => 122.60123400,
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);

        Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $objPaid->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'bill_number' => 'DLH-PAID-001',
            'amount' => 8500,
            'status' => 'PAID',
            'period' => $currentPeriod,
            'due_date' => Carbon::now()->addDays(15)->toDateString(),
        ]);

        // Persil 2: Menunggak / Overdue (Merah #EF4444)
        $objOverdue = TaxObject::create([
            'nop' => '74.72.010.001.001-0102.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Persil Menunggak',
            'address' => 'Jl. Pahlawan No. 2',
            'district' => 'Betoambari',
            'sub_district' => 'Katobengke',
            'latitude' => -5.46612300,
            'longitude' => 122.60223400,
            'building_category' => 'RUKO_NIAGA',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);

        Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $objOverdue->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'bill_number' => 'DLH-OVERDUE-001',
            'amount' => 22000,
            'status' => 'UNPAID',
            'period' => $currentPeriod,
            'due_date' => Carbon::now()->subDays(5)->toDateString(), // Jatuh tempo sudah lewat
        ]);

        $response = $this->getJson("/api/v1/dlh/spatial/compliance-map?period={$currentPeriod}");

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonPath('properties.summary.paid', 1)
            ->assertJsonPath('properties.summary.overdue', 1);

        $features = collect($response->json('features'));
        $paidFeature = $features->firstWhere('properties.tax_object_id', $objPaid->id);
        $overdueFeature = $features->firstWhere('properties.tax_object_id', $objOverdue->id);

        $this->assertEquals('#10B981', $paidFeature['properties']['marker_color']);
        $this->assertEquals('#EF4444', $overdueFeature['properties']['marker_color']);
    }

    public function test_executive_summary_report(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Data tiket karcis pasar
        DB::table('dlh_market_trash_tickets')->insert([
            'ticket_code' => 'KRC-001',
            'collector_user_id' => $this->admin->id,
            'market_name' => 'Pasar Nugraha',
            'amount' => 3000,
            'payment_method' => 'TUNAI',
            'qr_token' => 'TOKEN-1',
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $currentYear = (int) date('Y');
        $response = $this->getJson("/api/v1/dlh/reports/executive-summary?year={$currentYear}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.breakdown.retribusi_karcis_pasar.total_tickets_issued', 1)
            ->assertJsonPath('data.breakdown.retribusi_karcis_pasar.total_realized', 3000);
    }
}
