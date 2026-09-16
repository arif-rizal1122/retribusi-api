<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use App\Services\Dlh\DlhTrashBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DlhTrashBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Opd $dlh;
    private Taxpayer $taxpayer;
    private DlhTrashBillingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DlhTrashBillingService::class);

        DB::table('opds')->updateOrInsert(['id' => 4], [
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('retribution_types')->updateOrInsert(['id' => 17], [
            'opd_id' => 4,
            'name' => 'Retribusi Pelayanan Kebersihan',
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
            'nik' => '7472010101900001',
            'name' => 'La Ode Sampah',
            'phone' => '081234567890',
            'address' => 'Jl. Wolter Monginsidi No. 12',
        ]);

        $this->admin = User::create([
            'name' => 'Admin DLH',
            'email' => 'admin.dlh@baubaukota.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin_opd',
            'opd_id' => 4,
        ]);
    }

    public function test_tariff_calculation_for_all_building_categories(): void
    {
        $objSocial = new TaxObject(['building_category' => 'SOSIAL_IBADAH', 'status_hunian' => 'BERPENGHUNI']);
        $this->assertEquals(0.00, $this->service->calculateMonthlyAmount($objSocial));

        $objSimple = new TaxObject(['building_category' => 'RUMAH_SEDERHANA', 'status_hunian' => 'BERPENGHUNI']);
        $this->assertEquals(8500.00, $this->service->calculateMonthlyAmount($objSimple));

        $objMedium = new TaxObject(['building_category' => 'RUMAH_MENENGAH', 'status_hunian' => 'BERPENGHUNI']);
        $this->assertEquals(15000.00, $this->service->calculateMonthlyAmount($objMedium));

        $objShop = new TaxObject(['building_category' => 'RUKO_NIAGA', 'status_hunian' => 'BERPENGHUNI']);
        $this->assertEquals(22000.00, $this->service->calculateMonthlyAmount($objShop));

        $objResto = new TaxObject(['building_category' => 'RESTORAN', 'status_hunian' => 'BERPENGHUNI']);
        $this->assertEquals(45000.00, $this->service->calculateMonthlyAmount($objResto));

        // Objek rumah kosong bebas biaya sampah
        $objEmpty = new TaxObject(['building_category' => 'RUKO_NIAGA', 'status_hunian' => 'KOSONG']);
        $this->assertEquals(0.00, $this->service->calculateMonthlyAmount($objEmpty));
    }

    public function test_generate_monthly_bill_and_prevent_duplicates(): void
    {
        $taxObject = TaxObject::create([
            'nop' => '74.72.010.001.001-0001.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Persil Rumah Tinggal Sederhana',
            'address' => 'Jl. Pahlawan No. 45',
            'district' => 'Betoambari',
            'sub_district' => 'Katobengke',
            'building_category' => 'RUMAH_SEDERHANA',
            'tipe_penghuni' => 'PEMILIK',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'La Ode Sampah',
            'is_active' => true,
        ]);

        $periodDate = Carbon::create(2026, 9, 1);
        $bill = $this->service->generateMonthlyBill($taxObject, $periodDate);

        $this->assertInstanceOf(Bill::class, $bill);
        $this->assertEquals(8500.00, $bill->amount);
        $this->assertEquals('2026-09', $bill->period);
        $this->assertEquals('UNPAID', $bill->status);
        $this->assertEquals('2026-09-15', $bill->due_date ? Carbon::parse($bill->due_date)->format('Y-m-d') : null);

        // Uji idempoten: generate ulang di periode yang sama tidak menduplikasi record
        $billDuplicate = $this->service->generateMonthlyBill($taxObject, $periodDate);
        $this->assertEquals($bill->id, $billDuplicate->id);
        $this->assertEquals(1, Bill::where('tax_object_id', $taxObject->id)->count());
    }

    public function test_overdue_penalty_calculation_1_percent_per_month(): void
    {
        $taxObject = TaxObject::create([
            'nop' => '74.72.010.001.001-0002.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'building_category' => 'RUKO_NIAGA',
            'tipe_penghuni' => 'PEMILIK',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Pemilik Ruko',
            'name' => 'Ruko Perdagangan',
            'address' => 'Jl. Murhum',
            'is_active' => true,
        ]);

        // Tagihan terbit bulan Juli (jatuh tempo 15 Juli 2026)
        $julyBill = Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'bill_number' => 'DLH-SMP-260715-001',
            'amount' => 22000.00,
            'status' => 'UNPAID',
            'period' => '2026-07',
            'due_date' => '2026-07-15',
        ]);

        // Mock waktu saat ini adalah September 2026 (~2 bulan terlambat)
        Carbon::setTestNow(Carbon::create(2026, 9, 20));

        $updatedBill = $this->service->applyOverduePenalty($julyBill);

        $this->assertGreaterThan(0, $updatedBill->penalty_amount);
        // 2 bulan terlambat x 1% = 2% dari 22.000 = 440
        $this->assertEquals(440.00, $updatedBill->penalty_amount);

        Carbon::setTestNow(); // Reset mock waktu
    }

    public function test_api_transfer_occupant(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $taxObject = TaxObject::create([
            'nop' => '74.72.010.001.001-0003.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'building_category' => 'RUKO_NIAGA',
            'tipe_penghuni' => 'PEMILIK',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Pak Pemilik',
            'penanggung_jawab_kontak' => '0811111111',
            'name' => 'Ruko Sewa',
            'address' => 'Jl. Wolter Monginsidi',
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/dlh/trash/transfer-occupant/{$taxObject->id}", [
            'tipe_penghuni' => 'PENYEWA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Penyewa Baru Toko Kue',
            'penanggung_jawab_kontak' => '0822222222',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.tipe_penghuni', 'PENYEWA')
            ->assertJsonPath('data.penanggung_jawab_nama', 'Penyewa Baru Toko Kue');

        $this->assertDatabaseHas('tax_objects', [
            'id' => $taxObject->id,
            'tipe_penghuni' => 'PENYEWA',
            'penanggung_jawab_nama' => 'Penyewa Baru Toko Kue',
        ]);
    }

    public function test_api_bulk_generate_monthly_bills(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        // Buat 3 objek persil (2 berpenghuni, 1 kosong)
        TaxObject::create([
            'nop' => '74.72.010.001.001-0010.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Rumah 1',
            'address' => 'Jl. Dayanu Ikhsanuddin',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);

        TaxObject::create([
            'nop' => '74.72.010.001.001-0011.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Rumah 2',
            'address' => 'Jl. Dayanu Ikhsanuddin',
            'building_category' => 'RUMAH_MENENGAH',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);

        TaxObject::create([
            'nop' => '74.72.010.001.001-0012.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Rumah Kosong',
            'address' => 'Jl. Dayanu Ikhsanuddin',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'KOSONG',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/dlh/trash/generate-bills', [
            'period' => '2026-09',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.new_bills_generated', 2)
            ->assertJsonPath('data.total_amount_billed', 23500); // 8500 + 15000
    }

    public function test_public_zero_login_can_inquire_object_bill_without_auth(): void
    {
        $taxObject = TaxObject::create([
            'nop' => '74.72.010.001.001-9999.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Kios Pasar Wameo A-12',
            'address' => 'Jl. Pasar Baru No. 12',
            'building_category' => 'RUKO_NIAGA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Haji Mansyur Sulaeman',
            'is_active' => true,
        ]);

        Bill::create([
            'bill_number' => 'BILL-PUB-DLH-001',
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'period' => '2026-09',
            'amount' => 22000,
            'penalty_amount' => 0,
            'status' => 'UNPAID',
            'due_date' => '2026-09-15',
        ]);

        // Zero-Login: Tidak menggunakan Sanctum auth
        $response = $this->getJson("/api/v1/public/objects/{$taxObject->id}/bill");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.tax_object_id', $taxObject->id)
            ->assertJsonPath('data.penanggung_jawab', 'H*** M**** S****') // Masked
            ->assertJsonPath('data.status_bulan_ini', 'BELUM_LUNAS')
            ->assertJsonPath('data.total_tunggakan', 22000);

        // Uji inisiasi bayar publik via QRIS
        $payResponse = $this->postJson("/api/v1/public/objects/{$taxObject->id}/pay", [
            'method' => 'QRIS',
        ]);

        $payResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.method', 'QRIS')
            ->assertJsonPath('data.total_amount', 22000);
    }

    public function test_officer_can_report_empty_house_and_trigger_geofence_check(): void
    {
        Sanctum::actingAs($this->admin);

        $taxObject = TaxObject::create([
            'nop' => '74.72.010.001.001-8888.0',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Rumah Lapangan GPS',
            'address' => 'Jl. Murhum No. 45',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'latitude' => -5.46781200,
            'longitude' => 122.60543200,
            'penanggung_jawab_kontak' => '081234567890',
            'is_active' => true,
        ]);

        // Lapor dalam radius (<30m)
        $response = $this->postJson("/api/v1/dlh/trash/empty-house-notice/{$taxObject->id}", [
            'latitude' => -5.46781210,
            'longitude' => 122.60543210,
            'notes' => 'Pintu terkunci saat petugas datang',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status_hunian', 'KOSONG')
            ->assertJsonPath('data.is_within_geofence', true);

        $this->assertEquals('KOSONG', $taxObject->fresh()->status_hunian);
    }
}
