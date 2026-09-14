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
        Schema::create('asset_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_rental_id')->nullable()->constrained('asset_rentals')->nullOnDelete();
            $table->foreignId('asset_item_id')->constrained('asset_items')->cascadeOnDelete();
            $table->foreignId('inspector_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('inspection_type', ['PRE_OPERATION', 'POST_OPERATION'])->default('PRE_OPERATION');
            $table->decimal('hour_meter', 10, 2);
            $table->unsignedSmallInteger('fuel_level_percent')->nullable();
            $table->json('checklist')->nullable(); // engine, hydraulic, track_tires, cabin, electrical
            $table->text('condition_notes')->nullable();
            $table->json('photos')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_overtime')->default(false);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->foreignId('denda_penalty_bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->timestamp('inspected_at');
            $table->timestamps();

            $table->index(['asset_rental_id', 'inspection_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};
