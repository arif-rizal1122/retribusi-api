<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Titik/lokasi parkir Dishub (Quick-Tap Kasir Lapangan).
     * Tarif r2/r4 per lokasi mengikuti zonasi Perda No. 1 Tahun 2024
     * (Premium/Strategis/Ekonomi/Umum) dan dimuat server-side (source of truth).
     */
    public function up(): void
    {
        Schema::create('parking_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('retribusi_umum'); // retribusi_umum | retribusi_khusus | pajak_pbjt
            $table->foreignId('opd_id')->constrained()->onDelete('cascade');
            $table->foreignId('retribution_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('retribution_classification_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('rate_r2')->default(2000);
            $table->unsignedInteger('rate_r4')->default(3000);
            $table->text('base_qris_payload')->nullable();
            $table->string('nmid')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['opd_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_locations');
    }
};