<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data Surat Tugas jukir (untuk profil Jukir M-PAD).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surat_tugas_no')->nullable()->after('status');
            $table->timestamp('surat_tugas_expired_at')->nullable()->after('surat_tugas_no');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['surat_tugas_no', 'surat_tugas_expired_at']);
        });
    }
};