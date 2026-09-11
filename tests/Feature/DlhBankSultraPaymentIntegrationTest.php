<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Opd;
use App\Models\Payment;
use App\Models\PaymentChannel;
use App\Models\RetributionClassification;
use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DlhMasterTariffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DlhBankSultraPaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'secret_sultra_2026';
    protected User $adminDlh;
    protected Opd $dlhOpd;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.default' => 'sultra',
            'payment.drivers.sultra.secret' => $this->secret,
            'payment.drivers.sultra.allowed_ips' => ['127.0.0.1'],
        ]);

        PaymentChannel::updateOrCreate(
            ['code' => 'sultra'],
            ['name' => 'Bank Sultra', 'is_active' => true]
        );

        DB::table('opds')->updateOrInsert(['id' => 4], [
            'name' => 'Dinas Lingkungan Hidup',
            'code' => 'DLH',
            'status' => 'approved',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->dlhOpd = Opd::find(4);

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

        $this->seed(DlhMasterTariffSeeder::class);

        $this->adminDlh = User::create([
            'name' => 'Admin DLH',
            'email' => 'admin.dlh@baubaukota.go.id',
            'password' => bcrypt('password123'),
            'role' => 'admin_opd',
            'opd_id' => $this->dlhOpd->id,
            'status' => 'active',
        ]);
    }

    private function generateHmacSignature(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret);
    }

    public function test_dlh_trash_bill_can_be_inquired_and_paid_via_bank_sultra_h2h(): void
    {
        $taxpayer = Taxpayer::create([
            'opd_id' => $this->dlhOpd->id,
            'nik' => '7472010101900088',
            'name' => 'La Ode Sampah Wameo',
            'phone' => '081234567888',
            'address' => 'Jl. Pahlawan No. 44, Baubau',
        ]);

        $retType = RetributionType::updateOrCreate(
            ['id' => 17],
            [
                'opd_id' => $this->dlhOpd->id,
                'name' => 'Retribusi Pelayanan Kebersihan',
                'category' => 'JASA_UMUM',
                'is_active' => true,
            ]
        );

        $taxObject = TaxObject::create([
            'opd_id' => $this->dlhOpd->id,
            'taxpayer_id' => $taxpayer->id,
            'retribution_type_id' => $retType->id,
            'name' => 'Rumah Tinggal Sederhana La Ode',
            'address' => 'Jl. Pahlawan No. 44',
            'building_category' => 'RUMAH_SEDERHANA',
            'status_hunian' => 'BERPENGHUNI',
            'is_active' => true,
        ]);

        $period = date('Y-m');
        $bill = Bill::create([
            'bill_number' => 'SKRD-DLH-' . date('Ym') . '-0088',
            'taxpayer_id' => $taxpayer->id,
            'tax_object_id' => $taxObject->id,
            'retribution_type_id' => $retType->id,
            'amount' => 8500.00,
            'penalty_amount' => 0.00,
            'status' => 'pending',
            'period' => $period,
            'due_date' => Carbon::now()->addDays(20),
        ]);

        $timestamp = Carbon::now()->toDateTimeString();

        // 1. INQUIRY dari Bank Sultra ke sistem M-PAD
        // Signature inquiry = HMAC(bill_number + timestamp + 0)
        $inquirySig = $this->generateHmacSignature($bill->bill_number . $timestamp . '0');

        $inquiryRes = $this->postJson('/api/v1/bank/inquiry', [
            'bill_number' => $bill->bill_number,
        ], [
            'X-Signature' => $inquirySig,
            'X-Timestamp' => $timestamp,
        ]);

        $inquiryRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.bill_number', $bill->bill_number)
            ->assertJsonPath('data.amount_pokok', 8500)
            ->assertJsonPath('data.total_amount', 8500);

        // 2. PAYMENT NOTIFICATION dari Bank Sultra (Teller / ATM / Mobile Banking)
        $ntb = 'NTB-SULTRA-' . time() . '-001';
        $paySig = $this->generateHmacSignature($bill->bill_number . $timestamp . '8500');

        $payRes = $this->postJson('/api/v1/bank/payment', [
            'bill_number' => $bill->bill_number,
            'amount_paid' => 8500,
            'transaction_id' => $ntb,
            'channel' => 'TELLER_BANK_SULTRA',
        ], [
            'X-Signature' => $paySig,
            'X-Timestamp' => $timestamp,
        ]);

        $payRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'LUNAS');

        // Pastikan tagihan lunas dan record payment tersimpan dengan kode bank SULTRA
        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'status' => 'lunas',
            'bank_code' => 'SULTRA',
        ]);

        $this->assertDatabaseHas('payments', [
            'bill_id' => $bill->id,
            'reference_number' => $ntb,
            'amount' => 8500,
            'status' => 'success',
            'channel' => 'TELLER_BANK_SULTRA',
        ]);

        // 3. IDEMPOTENCY: Callback ulang dengan NTB yang sama tidak boleh duplicate
        $dupRes = $this->postJson('/api/v1/bank/payment', [
            'bill_number' => $bill->bill_number,
            'amount_paid' => 8500,
            'transaction_id' => $ntb,
            'channel' => 'TELLER_BANK_SULTRA',
        ], [
            'X-Signature' => $paySig,
            'X-Timestamp' => $timestamp,
        ]);

        $dupRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Transaksi duplikat (Idempotent Success)');
    }

    public function test_dlh_lab_order_bill_paid_via_bank_sultra_h2h(): void
    {
        Sanctum::actingAs($this->adminDlh, ['*']);

        $paramCod = DB::table('dlh_lab_parameters')->where('code', 'LAB-COD')->first();
        $paramBod = DB::table('dlh_lab_parameters')->where('code', 'LAB-BOD')->first();

        // 1. Buat Order Lab
        $orderRes = $this->postJson('/api/v1/dlh/lab/orders', [
            'applicant_name' => 'PT Baubau Sejahtera Lab',
            'institution_name' => 'Klinik Medika Bahari',
            'contact_phone' => '081234567999',
            'applicant_address' => 'Kawasan Industri Baubau',
            'sample_matrix' => 'AIR_LIMBAH',
            'sampling_location' => 'Outlet IPAL Wameo',
            'sampling_date' => date('Y-m-d'),
            'parameter_ids' => [$paramCod->id, $paramBod->id],
        ]);

        $orderRes->assertStatus(201);
        $orderId = $orderRes->json('data.order.id');
        $totalAmount = (float) $orderRes->json('data.order.total_amount'); // 175000.00

        // 2. Terbitkan SKRD Lab
        $billingRes = $this->postJson("/api/v1/dlh/lab/orders/{$orderId}/billing");
        $billingRes->assertStatus(200);
        $billNumber = $billingRes->json('data.bill_number');

        // 3. Bank Sultra Inquiry
        $timestamp = Carbon::now()->toDateTimeString();
        $inquirySig = $this->generateHmacSignature($billNumber . $timestamp . '0');

        $inquiryRes = $this->postJson('/api/v1/bank/inquiry', [
            'bill_number' => $billNumber,
        ], [
            'X-Signature' => $inquirySig,
            'X-Timestamp' => $timestamp,
        ]);

        $inquiryRes->assertStatus(200)
            ->assertJsonPath('data.amount_pokok', (int) $totalAmount);

        // 4. Bank Sultra Payment
        $ntb = 'NTB-LAB-SULTRA-' . time();
        $paySig = $this->generateHmacSignature($billNumber . $timestamp . (string) $totalAmount);

        $payRes = $this->postJson('/api/v1/bank/payment', [
            'bill_number' => $billNumber,
            'amount_paid' => $totalAmount,
            'transaction_id' => $ntb,
            'channel' => 'MOBILE_BANKING_SULTRA',
        ], [
            'X-Signature' => $paySig,
            'X-Timestamp' => $timestamp,
        ]);

        $payRes->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'LUNAS');

        $this->assertDatabaseHas('bills', [
            'bill_number' => $billNumber,
            'status' => 'lunas',
            'bank_code' => 'SULTRA',
        ]);
    }
}
