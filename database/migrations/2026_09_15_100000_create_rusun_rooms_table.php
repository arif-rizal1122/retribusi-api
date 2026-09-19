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
        Schema::create('rusun_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_item_id')->constrained('asset_items')->cascadeOnDelete();
            $table->string('complex_code', 20);       // kotamara, wameo, asn, mahasiswa
            $table->unsignedTinyInteger('floor');      // 1, 2, 3, 4
            $table->string('room_number', 10);         // 101, 102, 201, dst.
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->foreignId('current_rental_id')->nullable()->constrained('asset_rentals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['asset_item_id', 'room_number']);
            $table->index(['complex_code', 'floor', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rusun_rooms');
    }
};
