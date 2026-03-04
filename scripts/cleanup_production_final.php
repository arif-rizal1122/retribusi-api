<?php

/**
 * Bapenda Production Cleanup Script
 * Run this via: php artisan tinker /path/to/cleanup.php
 */

use App\Models\Taxpayer;
use App\Models\RetributionType;
use App\Models\User;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Verification;
use App\Models\TaxObject;

echo "🚀 Starting Bapenda Production Cleanup...\n";

// 1. Clean up Taxpayers & related data
$taxpayerPatterns = ['[TEST]', 'TESTING', 'uji coba', 'percobaan', 'dummy'];
$nikPatterns = ['99999999', '12345678', 'None'];

$deletedTpCount = 0;
Taxpayer::all()->each(function ($tp) use ($taxpayerPatterns, $nikPatterns, &$deletedTpCount) {
    $shouldDelete = false;
    $name = strtoupper($tp->name);
    
    foreach ($taxpayerPatterns as $pattern) {
        if (str_contains($name, $pattern)) {
            $shouldDelete = true;
            break;
        }
    }
    
    if (!$shouldDelete) {
        foreach ($nikPatterns as $nikPattern) {
            if (str_starts_with($tp->nik ?? '', $nikPattern)) {
                $shouldDelete = true;
                break;
            }
        }
    }

    if ($shouldDelete) {
        echo "Deleting Taxpayer: {$tp->name} (ID: {$tp->id})\n";
        // Manual cleanup of related data if cascading is not fully set
        Verification::where('taxpayer_id', $tp->id)->delete();
        Bill::where('taxpayer_id', $tp->id)->each(function($bill) {
            Payment::where('bill_id', $bill->id)->delete();
            $bill->delete();
        });
        TaxObject::where('taxpayer_id', $tp->id)->delete();
        $tp->delete();
        $deletedTpCount++;
    }
});
echo "✅ Deleted $deletedTpCount taxpayers.\n";

// 2. Clean up Retribution Types
$deletedRtCount = 0;
RetributionType::all()->each(function ($rt) use (&$deletedRtCount) {
    $name = strtolower($rt->name);
    if (str_contains($name, 'test') || str_contains($name, 'dummy')) {
        echo "Deleting Retribution Type: {$rt->name} (ID: {$rt->id})\n";
        // This might fail if there are constraints, so we use try-catch
        try {
            $rt->delete();
            $deletedRtCount++;
        } catch (\Exception $e) {
            echo "⚠️ Could not delete Retribution Type {$rt->name}: " . $e->getMessage() . "\n";
            echo "   Deactivating instead...\n";
            $rt->update(['is_active' => false]);
        }
    }
});
echo "✅ Cleaned up $deletedRtCount retribution types.\n";

// 3. Clean up Users (Non-admins)
$deletedUserCount = 0;
User::where('email', '!=', 'superadmin@sipanda.online')
    ->where('email', '!=', 'admin@baubaukota.go.id')
    ->get()
    ->each(function ($u) use (&$deletedUserCount) {
        $name = strtoupper($u->name);
        $email = strtolower($u->email);
        
        if (str_contains($name, '[TEST]') || str_contains($email, 'test')) {
            echo "Deleting User: {$u->name} ({$u->email})\n";
            try {
                $u->delete();
                $deletedUserCount++;
            } catch (\Exception $e) {
                echo "⚠️ Could not delete User {$u->email}: " . $e->getMessage() . "\n";
            }
        }
    });
echo "✅ Deleted $deletedUserCount test users.\n";

echo "\n✨ Cleanup process finished.\n";
