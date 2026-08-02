<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom catatan pada objek PBB warga — dipakai untuk mencatat sumber
     * validasi saat link NOP melalui fallback SPPT lokal.
     */
    public function up(): void
    {
        Schema::table('taxpayer_pbb_objects', function (Blueprint $table) {
            $table->string('notes', 255)->nullable()->after('is_verified');
        });
    }

    public function down(): void
    {
        Schema::table('taxpayer_pbb_objects', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
