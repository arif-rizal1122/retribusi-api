<?php

namespace Database\Seeders;

use App\Models\RetributionType;
use App\Models\TaxObject;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Verification;
use App\Models\ObjectVerification;
use App\Models\RetributionClassification;
use App\Models\RetributionRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanRetributionTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🧹 Cleaning up database to leave only Wilayah I & II...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Clear tables that depend on RetributionType
        $this->command->info('🗑️  Deleting dependent records...');
        Payment::truncate();
        Bill::truncate();
        ObjectVerification::truncate();
        Verification::truncate();
        TaxObject::truncate();
        RetributionRate::truncate();
        RetributionClassification::truncate();

        // 2. Delete all RetributionTypes except Wilayah I and Wilayah II
        $this->command->info('🗑️  Deleting extra retribution types...');
        RetributionType::whereNotIn('name', ['Wilayah I', 'Wilayah II'])->delete();

        // 3. Ensure Wilayah I and Wilayah II exist for BAPENDA (opd_id 4 or name BAPENDA)
        $bapenda = \App\Models\Opd::where('code', 'BAPENDA')->first();
        if ($bapenda) {
            RetributionType::updateOrCreate(
                ['name' => 'Wilayah I', 'opd_id' => $bapenda->id],
                ['category' => 'Pajak', 'is_active' => true]
            );
            RetributionType::updateOrCreate(
                ['name' => 'Wilayah II', 'opd_id' => $bapenda->id],
                ['category' => 'Pajak', 'is_active' => true]
            );
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ Clean up complete! Only Wilayah I and Wilayah II remain.');
        $this->command->info('💡 Tip: Run "php artisan db:seed --class=TaxHierarchySyncSeeder" to restore classifications.');
    }
}
