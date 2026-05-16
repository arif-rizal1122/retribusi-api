<?php

namespace Tests\Feature;

use App\Models\RetributionClassification;
use App\Models\Taxpayer;
use App\Models\User;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use App\Models\Bill;
use App\Models\TaxObject;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class SembilanPajakTest extends TestCase
{
    use RefreshDatabase;

    private $testReport = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database for testing
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $this->artisan('db:seed', ['--class' => 'TestingScenarioSeeder']);
        $this->artisan('db:seed', ['--class' => 'Perda12024Seeder']);
    }

    private function getDummyFormData($className)
    {
        if (Str::contains($className, 'Makan dan Minum')) {
            return ['omset_bulanan' => 50000000, 'omzet_penjualan' => 50000000, 'jumlah_meja' => 20, 'kapasitas_kursi' => 80];
        }
        if (Str::contains($className, 'Perhotelan')) {
            return ['omset_kamar' => 100000000, 'nilai_pembayaran' => 100000000, 'omset_lain' => 10000000, 'jumlah_kamar' => 50, 'tipe_hotel' => 'bintang_3'];
        }
        if (Str::contains($className, 'Reklame')) {
            return ['panjang' => 4, 'lebar' => 6, 'jumlah_sisi' => 1, 'jumlah_titik' => 1, 'durasi_hari' => 365, 'jenis_reklame' => 'billboard', 'nsr' => 10000000];
        }
        if (Str::contains($className, 'MBLB')) {
            return ['volume_m3' => 100, 'jenis_material' => 'pasir', 'volume' => 100, 'harga_patokan' => 50000];
        }
        if (Str::contains($className, 'Hiburan')) {
            return ['omset_bulanan' => 75000000, 'harga_tiket' => 75000000];
        }
        if (Str::contains($className, 'Listrik')) {
            return ['omset_bulanan' => 20000000, 'nilai_jual_tenaga_listrik' => 20000000];
        }
        if (Str::contains($className, 'Parkir')) {
            return ['omset_bulanan' => 15000000, 'luas_parkir' => 500, 'nilai_pembayaran' => 15000000];
        }
        if (Str::contains($className, 'Air Tanah')) {
            return ['volume_m3' => 500, 'nilai_perolehan_air' => 2000, 'volume' => 500, 'npa' => 2000];
        }
        if (Str::contains($className, 'Walet')) {
            return ['omset_bulanan' => 40000000, 'hasil_panen_kg' => 10, 'nilai_jual' => 40000000];
        }
        return ['omset_bulanan' => 10000000]; // Default fallback
    }

    public function test_sembilan_pajak_lifecycle()
    {
        $admin = User::where('role', 'opd')->first();
        if (!$admin) {
            $admin = User::factory()->create(['role' => 'opd']);
        }

        $taxpayer = Taxpayer::first();
        if (!$taxpayer) {
            $taxpayer = Taxpayer::factory()->create();
        }

        $targets = [
            'PBJT - Makan dan Minum',
            'PBJT - Jasa Perhotelan',
            'Pajak Reklame',
            'Pajak MBLB',
            'PBJT - Jasa Kesenian dan Hiburan',
            'PBJT - Tenaga Listrik',
            'PBJT - Jasa Parkir',
            'Air Tanah',
            'Pajak Sarang Burung Walet'
        ];

        $report = "# Laporan Hasil Pengujian E2E 9 Pajak (MPAD)\n\n";
        $report .= "Pengujian ini mengeksekusi lifecycle lengkap: Pendaftaran Objek -> Perhitungan Tagihan -> Pembayaran.\n\n";
        $report .= "| Jenis Pajak | Waktu Pendaftaran (ms) | Waktu Tagihan (ms) | Waktu Pembayaran (ms) | Total Tagihan (Rp) | Status Akhir |\n";
        $report .= "| :--- | :--- | :--- | :--- | :--- | :--- |\n";

        foreach ($targets as $targetName) {
            $classification = RetributionClassification::where('name', 'LIKE', "%{$targetName}%")->first();
            
            if (!$classification) {
                $report .= "| {$targetName} | NOT FOUND | - | - | - | FAILED |\n";
                continue;
            }

            $admin->opd_id = $classification->opd_id ?? $classification->retributionType->opd_id;
            $admin->save();

            Sanctum::actingAs($admin, ['*']);

            // 1. Pendaftaran Objek (Tax Object)
            $formData = $this->getDummyFormData($classification->name);
            $startObj = microtime(true);
            $responseObj = $this->postJson('/api/tax-objects', [
                'taxpayer_id' => $taxpayer->id,
                'retribution_type_id' => $classification->retribution_type_id,
                'retribution_classification_id' => $classification->id,
                'opd_id' => $admin->opd_id ?? Opd::first()->id,
                'name' => "Objek {$targetName}",
                'address' => 'Jl. Test No. 1',
                'zone_id' => 1,
                'form_data' => $formData
            ]);
            $endObj = microtime(true);
            $timeObj = round(($endObj - $startObj) * 1000);
            
            if ($responseObj->status() !== 201) {
                dump($responseObj->json());
            }
            $responseObj->assertStatus(201);
            $taxObjectId = $responseObj->json('data.id');

            // 2. Pembuatan Tagihan (Calculate & Bill)
            $startBill = microtime(true);
            $responseBill = $this->postJson('/api/bills', [
                'taxpayer_id' => $taxpayer->id,
                'tax_object_id' => $taxObjectId,
                'period' => date('Y-m'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'metadata' => $formData
            ]);
            $endBill = microtime(true);
            $timeBill = round(($endBill - $startBill) * 1000);
            
            if ($responseBill->status() !== 201) {
                dump("FAILED AT: " . $targetName);
                dump($responseBill->json());
            }

            $responseBill->assertStatus(201);
            $billId = $responseBill->json('data.id');
            $totalAmount = $responseBill->json('data.total_amount');

            // Cek apakah tagihan > 0 sebagai validasi perhitungan bisnis jalan
            $this->assertTrue($totalAmount > 0, "Calculation for $targetName yielded 0. Formula failed.");

            // 3. Pembayaran (Payment)
            $startPay = microtime(true);
            $responsePay = $this->postJson('/api/payments', [
                'tax_object_id' => $taxObjectId,
                'billing_period' => date('Y-m'),
                'amount' => $totalAmount,
                'payment_method' => 'transfer',
            ]);
            $endPay = microtime(true);
            $timePay = round(($endPay - $startPay) * 1000);
            
            $responsePay->assertStatus(201);

            // Cek status Lunas
            $bill = Bill::find($billId);
            $this->assertEquals('lunas', $bill->status, "Bill status did not change to lunas for $targetName");

            $formattedAmount = number_format($totalAmount, 0, ',', '.');
            $report .= "| {$targetName} | {$timeObj} | {$timeBill} | {$timePay} | {$formattedAmount} | SUCCESS |\n";
        }

        // Simpan hasil ke file markdown
        $path = base_path('tests/results');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        file_put_contents($path . '/09_Laporan_Test_9_Pajak.md', $report);

        $this->assertTrue(true); // Dummy assert
    }
}
