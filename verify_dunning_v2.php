<?php

use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\EnforcementNotice;
use Carbon\Carbon;
use Illuminate\Support\Str;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Memulai Verifikasi Eskalasi Teguran 2...\n";

// 1. Setup Data
$obj = TaxObject::first();
if (!$obj) {
    echo "❌ Tidak ada Objek Pajak untuk testing.\n";
    exit(1);
}

// 2. Create Overdue Bill
$bill = Bill::create([
    'user_id' => 1,
    'taxpayer_id' => $obj->taxpayer_id,
    'tax_object_id' => $obj->id,
    'retribution_type_id' => $obj->retribution_type_id,
    'bill_number' => 'INV-Dunning-' . strtoupper(Str::random(6)),
    'amount' => 500000,
    'status' => 'pending',
    'period' => '2026-03',
    'due_date' => Carbon::now()->subMonths(1)
]);

// 3. Create OLD Teguran 1 (15 days ago)
DB::table('enforcement_notices')->insert([
    'tax_object_id' => $obj->id,
    'type' => 'teguran_1',
    'number' => 'TEG1-TEST-' . strtoupper(Str::random(6)),
    'status' => 'sent',
    'created_by' => 1,
    'created_at' => Carbon::now()->subDays(15),
    'updated_at' => Carbon::now()->subDays(15),
    'bill_id' => $bill->id,
]);

echo "✅ Teguran 1 (Lama) dibuat manual di DB.\n";

// 4. Run Command
echo "🚀 Menjalankan eskalasi...\n";
Artisan::call('enforcements:generate-drafts');

// 5. Check for Teguran 2
$teguran2 = EnforcementNotice::where('tax_object_id', $obj->id)
    ->where('type', 'teguran_2')
    ->latest()
    ->first();

if ($teguran2) {
    echo "✨ BERHASIL: Teguran 2 otomatis terbit! Nomor: {$teguran2->number}\n";
} else {
    echo "❌ GAGAL: Teguran 2 tidak ditemukan.\n";
}

// Cleanup
$bill->delete();
EnforcementNotice::where('tax_object_id', $obj->id)->delete();
echo "🗑️ Cleanup selesai.\n";
