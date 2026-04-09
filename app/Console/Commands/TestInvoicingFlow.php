<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaxObject;
use App\Models\Taxpayer;
use App\Models\Bill;
use App\Models\MonthlyReport;
use App\Models\RetributionType;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TestInvoicingFlow extends Command
{
    protected $signature = 'test:invoicing {--clean : Hapus data testing setelah selesai}';
    protected $description = 'Simulasi End-to-End alur penagihan (Registration -> Report -> Bill -> Penalty)';

    public function handle()
    {
        $this->info('🧪 Memulai Simulasi Pengujian Invoicing M-PAD...');

        try {
            DB::beginTransaction();

            // 1. Persiapan Data (Setup Dummy WP & Object)
            $this->comment('1. Menyiapkan Wajib Pajak & Objek Pajak Simulasi...');
            $taxpayer = Taxpayer::first() ?: Taxpayer::create([
                'name' => 'WP Testing Invoicing',
                'email' => 'test-invoice@example.com',
                'password' => bcrypt('password'),
                'phone' => '08123456789'
            ]);

            $type = RetributionType::where('name', 'Wilayah II')->first() ?: RetributionType::first();
            
            $object = TaxObject::create([
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $type->id,
                'opd_id' => $type->opd_id ?? 1,
                'name' => 'Objek Pajak Simulasi Invoicing',
                'address' => 'Jl. Testing No. 123',
                'nop' => 'TEST-' . Str::random(8),
                'status' => 'active',
                'metadata' => [
                    'omset_penjualan' => 50000000,
                    'is_testing' => true
                ],
                'created_at' => Carbon::now()->subMonths(3) // Dibuat 3 bulan lalu untuk tes JIT
            ]);
            $this->info("✅ Objek Pajak Terdaftar: [{$object->nop}]");

            // 2. Simulasi JIT Billing (Just-In-Time)
            $this->comment("\n2. Menguji Mesin JIT Billing (Virtual Bills)...");
            $billingService = app(BillingService::class);
            $pendingPeriods = $billingService->getPendingPeriods($object);
            
            $this->info("   Ditemukan " . $pendingPeriods->count() . " periode tertunggak (Virtual).");
            foreach ($pendingPeriods->take(2) as $p) {
                $this->line("   - Periode {$p['period']}: Rp " . number_format($p['total_amount']));
            }

            // 3. Simulasi Self-Assessment (Laporan -> Bill)
            $this->comment("\n3. Mensimulasikan Alur Self-Assessment (Lapor -> Approve -> Bill)...");
            $report = MonthlyReport::create([
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $object->id,
                'period' => Carbon::now()->subMonth()->format('Y-m'),
                'turnover_amount' => 10000000,
                'tax_amount' => 1000000,
                'status' => 'approved',
                'validated_at' => Carbon::now(),
                'validated_by' => 1
            ]);

            $bill = Bill::create([
                'user_id' => 1,
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $object->id,
                'retribution_type_id' => $type->id,
                'bill_number' => 'INV-TEST-' . strtoupper(Str::random(6)),
                'amount' => 1000000,
                'status' => 'pending',
                'period' => $report->period,
                'due_date' => Carbon::now()->subDays(5) // Dibuat kadaluarsa 5 hari lalu
            ]);
            $this->info("✅ Bill Terbit & Expired: {$bill->bill_number} (Due: {$bill->due_date})");

            // 4. Simulasi Perhitungan Denda (Penalty Calculation)
            $this->comment("\n4. Menjalankan Mesin Perhitungan Denda...");
            $this->call('bills:calculate-penalties');
            
            $bill->refresh();
            if ($bill->penalty_amount > 0) {
                $this->info("✅ Denda Berhasil Dihitung: Rp " . number_format($bill->penalty_amount));
            } else {
                $this->warn("⚠️ Denda tidak bertambah. Periksa konfigurasi tarif/formula.");
            }

            // 5. Cleanup (Optional)
            if ($this->option('clean')) {
                $this->comment("\n🗑️ Membersihkan data simulasi...");
                $bill->delete();
                $report->delete();
                $object->delete();
                $this->info('✅ Data Simulasi Dihapus.');
            }

            DB::commit();
            $this->info("\n✨ Simulasi Pengujian Invoicing Selesai dengan Sukses!");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Simulasi Gagal: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
