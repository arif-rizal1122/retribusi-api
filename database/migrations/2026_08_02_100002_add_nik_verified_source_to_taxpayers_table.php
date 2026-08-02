<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom sumber verifikasi NIK (local/dukcapil/none) untuk jejak audit
     * validasi NIK saat registrasi warga.
     */
    public function up(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->string('nik_verified_source', 20)->nullable()->after('nik');
        });
    }

    public function down(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->dropColumn('nik_verified_source');
        });
    }
};
