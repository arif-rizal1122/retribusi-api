<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bill;
use App\Models\Payment;
use Carbon\Carbon;

$start = Carbon::parse('2026-03-31')->startOfDay();
$end = Carbon::parse('2026-04-29')->endOfDay();
$opdId = 5;

echo "Range: $start to $end\n";
echo "OPD ID: $opdId\n";

$billsCount = Bill::where('opd_id', $opdId)->whereBetween('created_at', [$start, $end])->count();
echo "Total Bills created in range: $billsCount\n";

$pendingCount = Bill::where('opd_id', $opdId)->where('status', 'pending')->whereBetween('created_at', [$start, $end])->count();
echo "Pending Bills created in range: $pendingCount\n";

$paymentsCount = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
    ->where('bills.opd_id', $opdId)
    ->whereBetween('payments.paid_at', [$start, $end])
    ->count();
echo "Payments made in range: $paymentsCount\n";

$revenue = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
    ->where('bills.opd_id', $opdId)
    ->whereBetween('payments.paid_at', [$start, $end])
    ->sum('amount');
echo "Total Revenue in range: " . number_format($revenue, 2) . "\n";

$classifiedRevenueCount = Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
    ->join('retribution_classifications', 'bills.retribution_classification_id', '=', 'retribution_classifications.id')
    ->where('bills.opd_id', $opdId)
    ->whereBetween('payments.paid_at', [$start, $end])
    ->count();
echo "Payments with classification: $classifiedRevenueCount\n";

$skippedRevenueCount = $paymentsCount - $classifiedRevenueCount;
echo "Payments SKIPPED (no classification): $skippedRevenueCount\n";

