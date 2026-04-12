<?php

namespace App\Tests;

use App\Models\TaxObject;
use App\Models\Bill;
use App\Services\BillingService;
use App\Http\Controllers\BillController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BillingLogicTest
{
    public function run()
    {
        echo "=== MEMULAI PENGUJIAN LOGIKA BILLING ===\n\n";

        $this->testDuplicatePrevention();
        $this->testPerformanceCap();
        $this->testAuditTagging();

        echo "\n=== PENGUJIAN SELESAI ===\n";
    }

    private function testDuplicatePrevention()
    {
        echo "[1/3] Menguji Pencegahan Duplikasi Tagihan... ";
        
        $obj = TaxObject::first();
        if (!$obj) {
            echo "SKIP (Tidak ada data Objek Pajak)\n";
            return;
        }

        $period = '2026-11'; // Safe future period for testing
        
        // Cleanup if exists from previous tests
        Bill::where('tax_object_id', $obj->id)->where('period', $period)->delete();

        $user = \App\Models\User::where('role', 'super_admin')->first();
        
        $request = new Request([
            'tax_object_id' => $obj->id,
            'period' => $period,
            'amount' => 100000,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
        $request->setUserResolver(fn() => $user);

        $controller = app(BillController::class);
        
        // First strike
        $res1 = $controller->store($request);
        
        // Second strike (Should fail)
        $res2 = $controller->store($request);

        if ($res1->status() === 201 && $res2->status() === 422) {
            echo "BERHASIL (Duplikasi ditolak)\n";
        } else {
            echo "GAGAL (Status 1: {$res1->status()}, Status 2: {$res2->status()})\n";
        }
        
        // Clean up
        Bill::where('tax_object_id', $obj->id)->where('period', $period)->delete();
    }

    private function testPerformanceCap()
    {
        echo "[2/3] Menguji Batas Performa (24 Bulan)... ";
        
        $obj = TaxObject::first();
        if (!$obj) {
            echo "SKIP\n";
            return;
        }

        // Mock created_at to 5 years ago
        $originalCreatedAt = $obj->created_at;
        $obj->created_at = Carbon::now()->subYears(5);
        
        $service = new BillingService();
        $periods = $service->getPendingPeriods($obj);

        // Should not exceed ~25-26 (24 + current month + potential boundary)
        if ($periods->count() <= 26) {
            echo "BERHASIL (Dibatasi ke {$periods->count()} periode)\n";
        } else {
            echo "GAGAL (Ditemukan {$periods->count()} periode - Terlalu banyak)\n";
        }
        
        $obj->created_at = $originalCreatedAt;
    }

    private function testAuditTagging()
    {
        echo "[3/3] Menguji Audit Tagging (is_manual)... ";
        
        $obj = TaxObject::first();
        $period = '2026-12';
        $user = \App\Models\User::where('role', 'super_admin')->first();
        
        $request = new Request([
            'tax_object_id' => $obj->id,
            'period' => $period,
            'amount' => 50000,
        ]);
        $request->setUserResolver(fn() => $user);

        $controller = app(BillController::class);
        $res = $controller->store($request);
        
        $bill = Bill::where('tax_object_id', $obj->id)->where('period', $period)->first();
        
        if ($bill && ($bill->metadata['is_manual'] ?? false) === true) {
            echo "BERHASIL (Metadata is_manual ditemukan)\n";
        } else {
            echo "GAGAL (Metadata tidak ditemukan)\n";
        }

        if ($bill) $bill->delete();
    }
}

// Global execution for internal use
(new BillingLogicTest())->run();
