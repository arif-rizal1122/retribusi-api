<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M21-1466 (F3.2): Bridging status lunas ke BPN (SPLP).
 * Menyimpan data SPLP yang dikirim/diterima dari KKP BPN.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->string('splp_number', 64)->nullable()->after('sspd_qr_hash');
            $table->string('splp_status', 50)->nullable()->after('splp_number');
            $table->timestamp('splp_issued_at')->nullable()->after('splp_status');
            $table->text('splp_notes')->nullable()->after('splp_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->dropColumn(['splp_number', 'splp_status', 'splp_issued_at', 'splp_notes']);
        });
    }
};
