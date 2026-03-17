<?php
/**
 * Script untuk memverifikasi apakah template PDF baru bisa di-render tanpa syntax error.
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OfficialDocumentService;
use App\Models\Taxpayer;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\EnforcementNotice;

$service = app(OfficialDocumentService::class);

echo "🛠️ Mengetes Render PDF Template Baru...\n";

try {
    // 1. SKT
    echo "- Testing SKT... ";
    $tp = Taxpayer::first();
    if ($tp) {
        $data = $service->generateSKT($tp);
        $pdf = view('pdf.skt', $data)->render();
        echo "OK\n";
    } else { echo "SKIP (No Taxpayer)\n"; }

    // 2. LKOK
    echo "- Testing LKOK... ";
    $to = TaxObject::first();
    if ($to) {
        $data = $service->generateLKOK($to);
        $pdf = view('pdf.lkok', $data)->render();
        echo "OK\n";
    } else { echo "SKIP (No TaxObject)\n"; }

    // 3. SSRD
    echo "- Testing SSRD... ";
    $bill = Bill::where('status', 'paid')->first() ?? Bill::first();
    if ($bill) {
        $data = $service->generateSSRD($bill);
        $pdf = view('pdf.ssrd', $data)->render();
        echo "OK\n";
    } else { echo "SKIP (No Bill)\n"; }

    // 4. STRD
    echo "- Testing STRD... ";
    if ($bill) {
        $data = $service->generateSTRD($bill);
        $pdf = view('pdf.strd', $data)->render();
        echo "OK\n";
    } else { echo "SKIP (No Bill)\n"; }

    // 5. SPMP
    echo "- Testing SPMP... ";
    $notice = EnforcementNotice::first();
    if ($notice) {
        $data = $service->generateSPMP($notice);
        $pdf = view('pdf.spmp', $data)->render();
        echo "OK\n";
    } else { echo "SKIP (No Notice)\n"; }

    echo "✅ Seluruh template PDF baru berhasil di-render tanpa error syntax.\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
