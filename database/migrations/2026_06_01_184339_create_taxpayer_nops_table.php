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
        Schema::create('taxpayer_nops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxpayer_id')->constrained('taxpayers')->onDelete('cascade');
            $table->string('nop', 18);
            $table->string('name')->nullable(); // Nama di SPPT, opsional
            $table->string('address')->nullable(); // Alamat Objek, opsional
            $table->timestamps();
            
            $table->unique(['taxpayer_id', 'nop']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxpayer_nops');
    }
};
