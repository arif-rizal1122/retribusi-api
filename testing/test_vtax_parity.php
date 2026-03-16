<?php
/**
 * V-Tax Parity Test Script
 * Validates: Schema changes, new models, PDF templates, controller logic
 * Standar: /noss (No Screenshot) — Semua validasi via CLI
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bill;
use App\Models\Payment;
use App\Models\EnforcementNotice;
use App\Models\Verification;
use App\Models\TaxTransaction;
use App\Models\TaxObject;
use App\Services\OfficialDocumentService;
use Illuminate\Support\Facades\Schema;

$passed = 0;
$failed = 0;
$skipped = 0;

function test($label, $result, &$passed, &$failed) {
    if ($result) {
        echo "  ✅ PASS: $label\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $label\n";
        $failed++;
    }
}

echo "\n🔬 V-TAX PARITY TEST SUITE\n";
echo str_repeat('=', 60) . "\n\n";

// =========================================================
// Part 1: Schema Verification
// =========================================================
echo "📋 Part 1: Schema Verification\n";
echo str_repeat('-', 40) . "\n";

test('bills.admin_fee exists', Schema::hasColumn('bills', 'admin_fee'), $passed, $failed);
test('bills.postponed_at exists', Schema::hasColumn('bills', 'postponed_at'), $passed, $failed);
test('bills.reason_postponed exists', Schema::hasColumn('bills', 'reason_postponed'), $passed, $failed);
test('payments.tendered_amount exists', Schema::hasColumn('payments', 'tendered_amount'), $passed, $failed);
test('payments.change_amount exists', Schema::hasColumn('payments', 'change_amount'), $passed, $failed);
test('enforcement_notices.bill_id exists', Schema::hasColumn('enforcement_notices', 'bill_id'), $passed, $failed);
test('enforcement_notices.amount_at_issue exists', Schema::hasColumn('enforcement_notices', 'amount_at_issue'), $passed, $failed);
test('enforcement_notices.rejected_at exists', Schema::hasColumn('enforcement_notices', 'rejected_at'), $passed, $failed);
test('enforcement_notices.rejection_notes exists', Schema::hasColumn('enforcement_notices', 'rejection_notes'), $passed, $failed);
test('verifications.is_assessment_final exists', Schema::hasColumn('verifications', 'is_assessment_final'), $passed, $failed);
test('verifications.assessed_at exists', Schema::hasColumn('verifications', 'assessed_at'), $passed, $failed);
test('tax_transactions table exists', Schema::hasTable('tax_transactions'), $passed, $failed);

echo "\n";

// =========================================================
// Part 2: Model Attribute Verification
// =========================================================
echo "📋 Part 2: Model Attribute Verification\n";
echo str_repeat('-', 40) . "\n";

$bill = new Bill();
test('Bill: admin_fee in fillable', in_array('admin_fee', $bill->getFillable()), $passed, $failed);
test('Bill: postponed_at in fillable', in_array('postponed_at', $bill->getFillable()), $passed, $failed);

$payment = new Payment();
test('Payment: tendered_amount in fillable', in_array('tendered_amount', $payment->getFillable()), $passed, $failed);
test('Payment: change_amount in fillable', in_array('change_amount', $payment->getFillable()), $passed, $failed);

$notice = new EnforcementNotice();
test('EnforcementNotice: bill_id in fillable', in_array('bill_id', $notice->getFillable()), $passed, $failed);
test('EnforcementNotice: rejection_notes in fillable', in_array('rejection_notes', $notice->getFillable()), $passed, $failed);
test('EnforcementNotice: has bill() relation', method_exists($notice, 'bill'), $passed, $failed);

$verification = new Verification();
test('Verification: is_assessment_final in fillable', in_array('is_assessment_final', $verification->getFillable()), $passed, $failed);
test('Verification: assessed_at in fillable', in_array('assessed_at', $verification->getFillable()), $passed, $failed);

$txn = new TaxTransaction();
test('TaxTransaction: model exists & has fillable', count($txn->getFillable()) > 5, $passed, $failed);
test('TaxTransaction: has taxObject() relation', method_exists($txn, 'taxObject'), $passed, $failed);

echo "\n";

// =========================================================
// Part 3: Bill total_amount includes admin_fee
// =========================================================
echo "📋 Part 3: Business Logic Verification\n";
echo str_repeat('-', 40) . "\n";

$testBill = new Bill();
$testBill->amount = 100000;
$testBill->admin_fee = 5000;
$testBill->penalty_amount = 2000;
$testBill->fixed_fine_amount = 0;
$testBill->surcharge_amount = 0;
$testBill->waived_penalty_amount = 0;
test('Bill total_amount includes admin_fee (107000)', $testBill->total_amount == 107000, $passed, $failed);

$testBill->waived_penalty_amount = 1000;
test('Bill total_amount with waiver (106000)', $testBill->total_amount == 106000, $passed, $failed);

echo "\n";

// =========================================================
// Part 4: TaxObject classification relation
// =========================================================
echo "📋 Part 4: TaxObject Classification Relation\n";
echo str_repeat('-', 40) . "\n";

$taxObject = new TaxObject();
test('TaxObject: has classification() relation', method_exists($taxObject, 'classification'), $passed, $failed);
test('TaxObject: retribution_classification_id in fillable', in_array('retribution_classification_id', $taxObject->getFillable()), $passed, $failed);

echo "\n";

// =========================================================
// Part 5: PDF Template Syntax Verification
// =========================================================
echo "📋 Part 5: PDF Template Syntax Verification\n";
echo str_repeat('-', 40) . "\n";

$templates = ['teguran', 'teguran_sptpd', 'spp', 'spmp', 'skrd', 'sspd', 'ssrd', 'strd', 'skt', 'lkok', 'sppt'];
foreach ($templates as $tpl) {
    $path = resource_path("views/pdf/{$tpl}.blade.php");
    $exists = file_exists($path);
    test("Template pdf.{$tpl} exists", $exists, $passed, $failed);
}

echo "\n";

// =========================================================
// Part 6: Teguran PDF Render Test
// =========================================================
echo "📋 Part 6: Teguran Template Render Test\n";
echo str_repeat('-', 40) . "\n";

try {
    $teguranData = [
        'type' => 'teguran_1',
        'number' => 'TEST/TGR/001',
        'taxpayer_name' => 'Test WP',
        'taxpayer_address' => 'Jl. Test No. 1',
        'tax_object_name' => 'Hotel Test',
        'period' => 'Januari 2026',
        'bill_number' => 'INV-TEST-001',
        'amount' => 1000000,
        'penalty' => 20000,
        'total' => 1020000,
        'terbilang' => 'Satu Juta Dua Puluh Ribu Rupiah',
        'date' => '16 Maret 2026',
        'qr_url' => 'https://test.local/verify',
    ];
    $html = view('pdf.teguran', $teguranData)->render();
    test('Teguran template renders without error', strlen($html) > 100, $passed, $failed);
    test('Teguran contains taxpayer name', str_contains($html, 'Test WP'), $passed, $failed);
    test('Teguran contains bill amount', str_contains($html, '1.000.000'), $passed, $failed);
} catch (\Exception $e) {
    echo "  ❌ FAIL: Teguran render error: {$e->getMessage()}\n";
    $failed++;
}

try {
    $sptpdData = [
        'number' => 'TEST/SPTPD/001',
        'taxpayer_name' => 'Test WP SPTPD',
        'npwpd' => 'NPWPD-TEST-001',
        'tax_object_name' => 'Restoran Test',
        'period' => 'Februari 2026',
        'date' => '16 Maret 2026',
        'qr_url' => 'https://test.local/verify',
    ];
    $html = view('pdf.teguran_sptpd', $sptpdData)->render();
    test('Teguran SPTPD renders without error', strlen($html) > 100, $passed, $failed);
    test('Teguran SPTPD contains NPWPD', str_contains($html, 'NPWPD-TEST-001'), $passed, $failed);
} catch (\Exception $e) {
    echo "  ❌ FAIL: Teguran SPTPD render error: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// =========================================================
// Part 7: EnforcementNotice Type Validation
// =========================================================
echo "📋 Part 7: Controller Type Allowlist\n";
echo str_repeat('-', 40) . "\n";

$controllerFile = file_get_contents(app_path('Http/Controllers/Pengawas/EnforcementNoticeController.php'));
$requiredTypes = ['teguran_1', 'teguran_2', 'paksa', 'penyitaan', 'jatuh_tempo', 'sptpd_warning'];
foreach ($requiredTypes as $type) {
    test("EnforcementNotice type '{$type}' in validation", str_contains($controllerFile, $type), $passed, $failed);
}

test('EnforcementNotice has reject method', str_contains($controllerFile, 'function reject'), $passed, $failed);
test('Status uses "proses" (not "draft")', str_contains($controllerFile, "'proses'"), $passed, $failed);
test('Status uses "disetujui" (not "approved")', str_contains($controllerFile, "'disetujui'"), $passed, $failed);
test('Status uses "ditolak" (not "rejected")', str_contains($controllerFile, "'ditolak'"), $passed, $failed);

echo "\n";

// =========================================================
// Part 8: Route Registration Check
// =========================================================
echo "📋 Part 8: Route Registration\n";
echo str_repeat('-', 40) . "\n";

$routeFile = file_get_contents(base_path('routes/api.php'));
test('Reject route registered', str_contains($routeFile, "enforcements/{id}/reject"), $passed, $failed);
test('PDF route registered', str_contains($routeFile, "enforcements/{id}/pdf"), $passed, $failed);
test('Approve route registered', str_contains($routeFile, "enforcements/{id}/approve"), $passed, $failed);

echo "\n";

// =========================================================
// Summary
// =========================================================
echo str_repeat('=', 60) . "\n";
$total = $passed + $failed;
echo "📊 HASIL: {$passed}/{$total} PASSED, {$failed} FAILED\n";
echo str_repeat('=', 60) . "\n";

if ($failed > 0) {
    echo "⚠️  Ada {$failed} tes yang GAGAL. Perlu perbaikan.\n";
    exit(1);
} else {
    echo "🎉 Semua tes V-Tax Parity BERHASIL!\n";
    exit(0);
}
