<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
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

        // A retry after settlement must reuse the same period row and preserve
        // the terminal status instead of issuing a replacement bill.
        $bill->update(['status' => 'PAID']);
        $billAfterSettlement = $this->service->generateMonthlyBill($taxObject, $periodDate);
        $this->assertSame($bill->id, $billAfterSettlement->id);
        $this->assertSame('PAID', $billAfterSettlement->getRawOriginal('status'));
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

    public function test_paid_status_alias_does_not_receive_overdue_penalty(): void
    {
        $taxObject = TaxObject::create([
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'name' => 'Rumah Sudah Lunas',
            'address' => 'Jl. Lunas',
            'is_active' => true,
        ]);
        $bill = Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'bill_number' => 'DLH-SMP-PAID-PENALTY-001',
            'amount' => 8500,
            'status' => 'PAID',
            'period' => '2026-07',
            'due_date' => '2026-07-15',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 9, 20));

        $updatedBill = $this->service->applyOverduePenalty($bill);

        $this->assertSame('PAID', $updatedBill->getRawOriginal('status'));
        $this->assertEquals(0.0, (float) $updatedBill->penalty_amount);

        Carbon::setTestNow();
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

    public function test_citizen_inquiry_is_owner_scoped_to_an_exact_building_id(): void
    {
        $taxObject = TaxObject::create([
            'nop' => 'SMP-74-OWNER-001',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Rumah Pemilik',
            'address' => 'Jl. Pemilik',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'La Ode Sampah',
            'is_active' => true,
        ]);

        Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'bill_number' => 'DLH-SMP-OWNER-001',
            'amount' => 8500,
            'admin_fee' => 500,
            'penalty_amount' => 0,
            'status' => 'UNPAID',
            'period' => '2026-09',
            'due_date' => now()->subDay(),
        ]);

        // Another object under the same citizen must not bleed into this inquiry.
        $otherObject = TaxObject::create([
            'nop' => 'SMP-74-OWNER-002',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Kios Pemilik',
            'address' => 'Jl. Kios',
            'building_category' => 'RUKO_NIAGA',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);
        Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $otherObject->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'bill_number' => 'DLH-SMP-OWNER-002',
            'amount' => 22000,
            'status' => 'UNPAID',
            'period' => '2026-09',
            'due_date' => '2026-09-15',
        ]);

        Sanctum::actingAs($this->taxpayer, ['*']);

        $response = $this->getJson('/api/v1/dlh/trash/inquiry?query=SMP-74-OWNER-001');

        $response->assertOk()
            ->assertJsonPath('data.object_code', 'SMP-74-OWNER-001')
            ->assertJsonPath('data.active_bill.bill_number', 'DLH-SMP-OWNER-001')
            ->assertJsonPath('data.active_bill.total_amount', 9000)
            ->assertJsonPath('data.active_bill.status', 'overdue')
            ->assertJsonPath('data.active_bill.status_raw', 'UNPAID')
            ->assertJsonPath('data.active_bill.status_label', 'Jatuh tempo')
            ->assertJsonPath('data.active_bill.can_pay', true)
            ->assertJsonCount(1, 'data.bill_history');
    }

    public function test_citizen_inquiry_normalizes_paid_bill_without_making_it_payable(): void
    {
        $taxObject = TaxObject::create([
            'nop' => 'SMP-74-PAID-001',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Rumah Lunas',
            'address' => 'Jl. Lunas',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'La Ode Lunas',
            'is_active' => true,
        ]);

        Bill::create([
            'taxpayer_id' => $this->taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'bill_number' => 'DLH-SMP-PAID-001',
            'amount' => 8500,
            'status' => 'PAID',
            'period' => '2026-08',
            'due_date' => now()->subMonth(),
        ]);

        Sanctum::actingAs($this->taxpayer, ['*']);

        $this->getJson('/api/v1/dlh/trash/inquiry?query=SMP-74-PAID-001')
            ->assertOk()
            ->assertJsonPath('data.active_bill.status', 'paid')
            ->assertJsonPath('data.active_bill.status_raw', 'PAID')
            ->assertJsonPath('data.active_bill.status_label', 'Lunas')
            ->assertJsonPath('data.active_bill.can_pay', false)
            ->assertJsonPath('data.bill_history.0.status', 'paid');
    }

    public function test_citizen_cannot_inquire_another_citizens_building_or_by_nik(): void
    {
        $otherTaxpayer = Taxpayer::create([
            'opd_id' => 4,
            'nik' => '7472010101900099',
            'name' => 'Warga Lain',
            'phone' => '081234567891',
            'address' => 'Jl. Lain',
        ]);
        TaxObject::create([
            'nop' => 'SMP-74-OTHER-001',
            'taxpayer_id' => $otherTaxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Objek Warga Lain',
            'address' => 'Jl. Rahasia',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->taxpayer, ['*']);

        $this->getJson('/api/v1/dlh/trash/inquiry?query=SMP-74-OTHER-001')
            ->assertNotFound();

        $this->getJson('/api/v1/dlh/trash/inquiry?query=7472010101900099')
            ->assertNotFound();
    }

    public function test_trash_inquiry_rejects_non_identifier_search_terms(): void
    {
        Sanctum::actingAs($this->taxpayer, ['*']);

        $this->getJson('/api/v1/dlh/trash/inquiry?query=Jl.%20Wolter%20Monginsidi')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    public function test_citizen_cannot_run_dlh_mutations(): void
    {
        $taxObject = TaxObject::create([
            'nop' => 'SMP-74-MUTATION-001',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Objek Mutasi',
            'address' => 'Jl. Mutasi',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Pemilik Lama',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->taxpayer, ['*']);

        $this->postJson("/api/v1/dlh/trash/transfer-occupant/{$taxObject->id}", [
            'tipe_penghuni' => 'PENYEWA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Pemilik Baru',
        ])->assertForbidden();

        $this->postJson('/api/v1/dlh/trash/generate-bills', [
            'period' => '2026-09',
        ])->assertForbidden();

        $this->assertDatabaseHas('tax_objects', [
            'id' => $taxObject->id,
            'penanggung_jawab_nama' => 'Pemilik Lama',
        ]);
    }

    public function test_non_dlh_internal_user_cannot_mutate_dlh_occupant(): void
    {
        $otherOpd = Opd::create([
            'name' => 'Dinas Lain',
            'code' => 'OPD-LAIN',
        ]);
        $otherUser = User::create([
            'name' => 'Operator Lain',
            'email' => 'operator.lain@baubaukota.go.id',
            'password' => bcrypt('password'),
            'role' => 'admin_opd',
            'opd_id' => $otherOpd->id,
        ]);
        $taxObject = TaxObject::create([
            'nop' => 'SMP-74-CROSS-001',
            'taxpayer_id' => $this->taxpayer->id,
            'opd_id' => 4,
            'retribution_type_id' => 17,
            'retribution_classification_id' => 198,
            'name' => 'Objek Lintas OPD',
            'address' => 'Jl. Lintas',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Pemilik DLH',
            'is_active' => true,
        ]);

        Sanctum::actingAs($otherUser, ['*']);

        $this->postJson("/api/v1/dlh/trash/transfer-occupant/{$taxObject->id}", [
            'tipe_penghuni' => 'PENYEWA',
            'status_hunian' => 'BERPENGHUNI',
            'penanggung_jawab_nama' => 'Operator Lain',
        ])->assertForbidden();
    }
}
