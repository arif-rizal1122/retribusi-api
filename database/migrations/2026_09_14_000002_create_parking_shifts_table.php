<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shift harian jukir. Satu shift "open" aktif per jukir per tanggal.
     * Tutup shift mengunci buku penampungan (70% RKUD / 30% Jukir).
     */
    public function up(): void
    {
        Schema::create('parking_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parking_location_id')->constrained('parking_locations')->onDelete('cascade');
            $table->date('shift_date');
            $table->string('shift_status')->default('open'); // open | closed
            $table->timestamp('shift_opened_at')->nullable();
            $table->timestamp('shift_closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'shift_date', 'shift_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_shifts');
    }
};