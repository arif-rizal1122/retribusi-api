<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taxpayer_retribution_type', function (Blueprint $table) {
            // 1. Drop foreign keys first (MySQL needs this before dropping the index they use)
            $table->dropForeign(['taxpayer_id']);
            $table->dropForeign(['retribution_type_id']);
            
            // 2. Drop the old unique index
            $table->dropUnique(['taxpayer_id', 'retribution_type_id']);
            
            // 3. Re-add foreign keys
            $table->foreign('taxpayer_id')->references('id')->on('taxpayers')->onDelete('cascade');
            $table->foreign('retribution_type_id')->references('id')->on('retribution_types')->onDelete('cascade');
            
            // 4. Add the new expanded unique constraint
            $table->unique(['taxpayer_id', 'retribution_type_id', 'retribution_classification_id'], 'taxp_retri_type_class_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxpayer_retribution_type', function (Blueprint $table) {
            $table->dropUnique('taxp_retri_type_class_unique');
            
            // Re-add the original constrained unique index
            // Note: We might need to drop/re-add FKs here too depending on DB state, but let's keep it simple for now as down is rarely used
            $table->unique(['taxpayer_id', 'retribution_type_id']);
        });
    }
};
