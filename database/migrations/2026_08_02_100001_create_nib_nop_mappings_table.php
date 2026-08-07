<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapping NIB (BPN/BHUMI) ke NOP (PBB) — kerangka H2H BPN/BHUMI.
     */
    public function up(): void
    {
        Schema::create('nib_nop_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('nib', 16)->unique();
            $table->string('nop', 18)->nullable()->index();
            $table->string('taxpayer_name')->nullable();
            $table->decimal('land_area_m2', 15, 2)->nullable();
            $table->decimal('building_area_m2', 15, 2)->nullable();
            $table->string('znt')->nullable();
            $table->enum('match_status', ['pending', 'matched', 'conflict', 'not_found'])->default('pending');
            $table->enum('source', ['bhumi', 'manual'])->default('manual');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nib_nop_mappings');
    }
};
