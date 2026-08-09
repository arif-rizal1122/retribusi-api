<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fondasi M36: mapping unit usaha kalkulator bisnis → titik/lapak adopsi.
     * Fase awal — sinkronisasi penuh menyusul di milestone berikutnya.
     */
    public function up(): void
    {
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxpayer_id')->nullable()->constrained('taxpayers')->onDelete('cascade');
            $table->string('business_name');
            $table->string('unit_type')->nullable();
            $table->string('location_ref')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamp('first_transaction_at')->nullable();
            $table->timestamps();

            $table->index(['taxpayer_id', 'status']);
            $table->index('location_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_units');
    }
};
