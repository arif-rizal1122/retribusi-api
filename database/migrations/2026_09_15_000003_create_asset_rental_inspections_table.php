<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasil inspeksi fisik pra/pasca operasi unit + geotagging + penagihan overtime.
     * Setiap entry mencatat hour meter, fuel, checklist komponen fisik, koordinat GPS petugas.
     */
    public function up(): void
    {
        Schema::create('asset_rental_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_rental_id')->constrained('asset_rentals')->onDelete('cascade');
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_type'); // pre_operation | post_operation
            $table->decimal('hour_meter', 12, 2)->nullable();
            $table->unsignedTinyInteger('fuel_level_percent')->nullable();
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable(); // condition_notes / damage_notes
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_overtime')->default(false);
            $table->decimal('overtime_hours', 10, 2)->nullable();
            $table->decimal('overtime_rate', 15, 2)->nullable();
            $table->decimal('overtime_amount', 15, 2)->nullable();
            $table->foreignId('denda_bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->index(['asset_rental_id', 'inspection_type']);
            $table->index('inspected_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_rental_inspections');
    }
};