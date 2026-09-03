<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan unique index pada payments.reference_number.
     *
     * MySQL mengizinkan banyak NULL di kolom unique, sehingga baris lama
     * yang reference_number-nya NULL tetap aman. Duplikasi reference_number
     * yang masih ada di-scrub (rename) sebelum migrasi ini dijalankan.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unique('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['reference_number']);
        });
    }
};
