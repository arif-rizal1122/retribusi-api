<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Taxpayer;
use App\Models\RetributionClassification;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Scopes\RetributionTypeScope;

$adminEmails = ['adminw1@baubaukota.go.id', 'adminw2@baubaukota.go.id'];

foreach ($adminEmails as $email) {
    // 1. Get Admin (Bypass scope to find them)
    $admin = User::withoutGlobalScope(RetributionTypeScope::class)->where('email', $email)->first();
    if (!$admin) {
        echo "❌ Admin $email not found. Skipping...\n";
        continue;
    }

    echo "\n" . str_repeat('=', 50) . "\n";
    echo "TESTING FOR: " . $admin->name . " (Type ID: " . $admin->retribution_type_id . ")\n";
    echo str_repeat('=', 50) . "\n";

    // Reset scope for each test iteration
    RetributionTypeScope::resetUser();
    RetributionTypeScope::setAuthenticatedUser($admin);

    $results = [];

    // --- TAXPAYERS ---
    $filteredTaxpayers = Taxpayer::count();
    $invalidTaxpayers = Taxpayer::whereDoesntHave('taxObjects', function($q) use ($admin) {
        $q->where('retribution_type_id', $admin->retribution_type_id);
    })->count();
    $results['Taxpayers'] = ['count' => $filteredTaxpayers, 'invalid' => $invalidTaxpayers];

    // --- CLASSIFICATIONS ---
    $filteredClassifications = RetributionClassification::count();
    $invalidClassifications = RetributionClassification::where('retribution_type_id', '!=', $admin->retribution_type_id)->count();
    $results['Classifications'] = ['count' => $filteredClassifications, 'invalid' => $invalidClassifications];

    // --- TAX OBJECTS ---
    $filteredObjects = TaxObject::count();
    $invalidObjects = TaxObject::where('retribution_type_id', '!=', $admin->retribution_type_id)->count();
    $results['TaxObjects'] = ['count' => $filteredObjects, 'invalid' => $invalidObjects];

    // --- BILLS ---
    $filteredBills = Bill::count();
    $invalidBills = Bill::where('retribution_type_id', '!=', $admin->retribution_type_id)->count();
    $results['Bills'] = ['count' => $filteredBills, 'invalid' => $invalidBills];

    // --- PAYMENTS ---
    $filteredPayments = Payment::count();
    $invalidPayments = Payment::whereHas('bill', function($q) use ($admin) {
        $q->where('retribution_type_id', '!=', $admin->retribution_type_id);
    })->count();
    $results['Payments'] = ['count' => $filteredPayments, 'invalid' => $invalidPayments];

    // --- PETUGAS (Users) ---
    $filteredPetugas = User::where('role', 'petugas')->count();
    $invalidPetugas = User::where('role', 'petugas')
        ->where('retribution_type_id', '!=', $admin->retribution_type_id)
        ->count();
    $results['Petugas'] = ['count' => $filteredPetugas, 'invalid' => $invalidPetugas];

    // Output Results for this Admin
    foreach ($results as $model => $stats) {
        $status = $stats['invalid'] === 0 ? "✅ OK" : "❌ FAIL ({$stats['invalid']} leaked)";
        echo sprintf("%-15s : %5d records | %s\n", $model, $stats['count'], $status);
    }
}

echo "\nVerification Finished.\n";
