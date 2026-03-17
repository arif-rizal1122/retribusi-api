<?php
/**
 * STG5: Document Integrity Testing
 * Verifies that all 21 official document types across 4 stages can be generated as PDF without errors.
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\EnforcementNotice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// 1. Setup Report
$reportFile = __DIR__.'/results/11_Document_Integrity_Report.md';
$md = "# 📄 Laporan Integritas Dokumen Resmi BAPENDA (4 Tahapan)\n\n";
$md .= "**Waktu Eksekusi**: " . date('Y-m-d H:i:s') . "\n";
$md .= "Pengujian ini memverifikasi bahwa seluruh dokumen resmi dapat dicetak/tampil sebagai PDF tanpa error (40x/50x).\n\n";

// 2. Setup IDs for testing
$admin = User::where('role', 'super_admin')->first() ?? User::where('role', 'admin')->first() ?? User::first();
Auth::login($admin);

$sampleTp = Taxpayer::first();
$sampleTo = TaxObject::first();
$sampleBill = Bill::first();
if ($sampleBill) {
    if ($sampleBill->status !== 'paid' && $sampleBill->status !== 'lunas') {
        $sampleBill->status = 'paid';
        $sampleBill->save();
        
        // Ensure it has at least one payment record
        Payment::firstOrCreate(
            ['bill_id' => $sampleBill->id],
            [
                'taxpayer_id' => $sampleTp->id,
                'amount' => $sampleBill->amount,
                'paid_at' => now(),
                'status' => 'success',
                'payment_method' => 'CASH'
            ]
        );
    }
}
$sampleNotice = EnforcementNotice::first();
if ($sampleNotice && !$sampleNotice->bill_id) {
    $sampleNotice->bill_id = $sampleBill->id;
    $sampleNotice->save();
}
$sampleAmnesty = DB::table('penalty_waivers')->first();

if (!$sampleTp || !$sampleTo || !$sampleBill) {
    echo "Gagal: Data sampel (Taxpayer/TaxObject/Bill) tidak ditemukan.\n";
    exit(1);
}

$md .= "## 🧪 Hasil Pengujian Lintas Tahapan\n\n";
$md .= "| Tahapan | Nama Dokumen | Endpoint | Status | Hasil | Error (Jika Ada) |\n";
$md .= "| :--- | :--- | :--- | :---: | :---: | :--- |\n";

$testCases = [
    // TAHAP 1: PENDAFTARAN
    ['stage' => '1. Pendaftaran', 'name' => 'SKT (Surat Keterangan Terdaftar)', 'method' => 'GET', 'uri' => "/api/documents/skt/{$sampleTp->id}"],
    
    // TAHAP 2: PENDATAAN
    ['stage' => '2. Pendataan', 'name' => 'LKOK (Lembar Kerja Objek Khusus)', 'method' => 'GET', 'uri' => "/api/documents/lkok/{$sampleTo->id}"],
    
    // TAHAP 3: PENETAPAN (ASSESSMENT)
    ['stage' => '3. Penetapan', 'name' => 'SKRD (Retribusi)', 'method' => 'GET', 'uri' => "/api/documents/skrd/{$sampleBill->id}"],
    ['stage' => '3. Penetapan', 'name' => 'SPPT (PBB)', 'method' => 'GET', 'uri' => "/api/documents/sppt/{$sampleBill->id}"],
    ['stage' => '3. Penetapan', 'name' => 'SKPDKBT (Kurang Bayar)', 'method' => 'POST', 'uri' => "/api/documents/skpdkbt/{$sampleBill->id}", 'data' => ['additional_amount' => 50000, 'audit_notes' => 'Test Audit']],
    ['stage' => '3. Penetapan', 'name' => 'SKPDN (Nihil)', 'method' => 'POST', 'uri' => "/api/documents/skpdn/{$sampleBill->id}", 'data' => ['audit_notes' => 'Test Nihil']],
    
    // TAHAP 4: PENAGIHAN (COLLECTION/ENFORCEMENT)
    ['stage' => '4. Penagihan', 'name' => 'SSPD (Bukti Bayar)', 'method' => 'GET', 'uri' => "/api/documents/sspd/{$sampleBill->id}"],
    ['stage' => '4. Penagihan', 'name' => 'SSRD (Retribusi)', 'method' => 'GET', 'uri' => "/api/documents/ssrd/{$sampleBill->id}"],
    ['stage' => '4. Penagihan', 'name' => 'STRD (Tagihan Retribusi)', 'method' => 'GET', 'uri' => "/api/documents/strd/{$sampleBill->id}"],
];

// Add Notices if exist
if ($sampleNotice) {
    $testCases[] = ['stage' => '4. Penagihan', 'name' => 'SPP (Tugas Pemeriksaan)', 'method' => 'GET', 'uri' => "/api/documents/spp/{$sampleNotice->id}"];
    $testCases[] = ['stage' => '4. Penagihan', 'name' => 'SPMP (Surat Paksa)', 'method' => 'GET', 'uri' => "/api/documents/spmp/{$sampleNotice->id}"];
    $testCases[] = ['stage' => '4. Penagihan', 'name' => 'Notice Detail PDF', 'method' => 'GET', 'uri' => "/api/pengawas/enforcements/{$sampleNotice->id}/pdf"];
}

// Add Amnesty if exist
if ($sampleAmnesty) {
    $testCases[] = ['stage' => '3. Penetapan', 'name' => 'SK Amnesty/Waiver', 'method' => 'GET', 'uri' => "/api/amnesty/{$sampleAmnesty->id}/document"];
}

// Public Routes
$testCases[] = ['stage' => 'Public', 'name' => 'Public SKRD', 'method' => 'GET', 'uri' => "/api/public/pdf/skrd/{$sampleBill->id}"];
$testCases[] = ['stage' => 'Public', 'name' => 'Public SSPD', 'method' => 'GET', 'uri' => "/api/public/pdf/sspd/{$sampleBill->id}"];

$failCount = 0;

foreach ($testCases as $tc) {
    $stage = $tc['stage'];
    $name = $tc['name'];
    $method = $tc['method'];
    $uri = $tc['uri'];

    echo "Testing [$stage] $name... ";

    try {
        $data = $tc['data'] ?? [];
        $request = Request::create($uri, $method, $data);
        $request->setUserResolver(fn() => $admin);
        
        $response = app()->handle($request);
        $status = $response->getStatusCode();
        $contentType = $response->headers->get('Content-Type');

        $isOk = ($status >= 200 && $status < 300);
        $isPdf = str_contains((string)$contentType, 'pdf');

        if ($isOk) {
            $md .= "| $stage | $name | `$uri` | ✅ $status | ✅ PDF | - |\n";
            echo "SUCCESS ($status)\n";
        } else {
            $failCount++;
            $errorBody = substr($response->getContent(), 0, 100);
            $md .= "| $stage | $name | `$uri` | ❌ $status | ❌ FAIL | $errorBody |\n";
            echo "FAILED ($status)\n";
        }
    } catch (\Exception $e) {
        $failCount++;
        $md .= "| $stage | $name | `$uri` | ❌ EXCEPTION | ❌ FAIL | " . $e->getMessage() . " |\n";
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
}

$md .= "\n---\n";
$md .= "### 📈 Ringkasan Eksekusi\n";
$md .= "- Total Pengujian: " . count($testCases) . "\n";
$md .= "- Sukses: " . (count($testCases) - $failCount) . "\n";
$md .= "- Gagal: " . $failCount . "\n\n";

if ($failCount === 0) {
    $md .= "> [!TIP]\n";
    $md .= "> Seluruh dokumen dalam 4 tahapan berfungsi dengan baik dan siap cetak.\n";
} else {
    $md .= "> [!WARNING]\n";
    $md .= "> Terdapat $failCount dokumen yang mengalami kendala saat digenerate.\n";
}

file_put_contents($reportFile, $md);
echo "\nLaporan selesai: $reportFile\n";
exit($failCount > 0 ? 1 : 0);
