<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Migrate existing zone amounts to retribution_rates (if any exist)
        $zones = DB::table('zones')->whereNotNull('amount')->where('amount', '>', 0)->get();
        
        foreach ($zones as $zone) {
            // Check if a rate already exists for this zone
            $existingRate = DB::table('retribution_rates')
                ->where('zone_id', $zone->id)
                ->where('retribution_type_id', $zone->retribution_type_id)
                ->first();
            
            if (!$existingRate) {
                DB::table('retribution_rates')->insert([
                    'opd_id' => $zone->opd_id,
                    'retribution_type_id' => $zone->retribution_type_id,
                    'retribution_classification_id' => $zone->retribution_classification_id ?? null,
                    'zone_id' => $zone->id,
                    'name' => 'Tarif ' . $zone->name,
                    'amount' => $zone->amount,
                    'unit' => 'unit',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Step 2: Drop the columns from zones
        Schema::table('zones', function (Blueprint $table) {
            if (Schema::hasColumn('zones', 'amount')) {
                $table->dropColumn('amount');
            }
            if (Schema::hasColumn('zones', 'multiplier')) {
                $table->dropColumn('multiplier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->decimal('multiplier', 8, 2)->default(1)->after('code');
            $table->decimal('amount', 15, 2)->nullable()->after('multiplier');
        });
    }
};
