<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah dukungan NIB pada rincian kavling hasil pecah bidang.
 *
 * POSISI NIB: hasil TERBITAN setelah verifikasi BPN, BUKAN diisi pendaftar.
 *   - nib_status: BELUM_TERBIT (awal) -> MENUNGGU_BPN -> TERBIT
 *   - nib: nomor Induk Bidang yang diterbitkan Kantah ATR/BPN
 *   - nib_issued_at: waktu NIB dicatat/terbit
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbb_split_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pbb_split_items', 'nib')) {
                $table->string('nib', 30)->nullable()->after('generated_nop')->index();
            }
            if (!Schema::hasColumn('pbb_split_items', 'nib_status')) {
                $table->string('nib_status', 32)->default('BELUM_TERBIT')->after('nib');
            }
            if (!Schema::hasColumn('pbb_split_items', 'nib_issued_at')) {
                $table->timestamp('nib_issued_at')->nullable()->after('nib_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pbb_split_items', function (Blueprint $table) {
            foreach (['nib', 'nib_status', 'nib_issued_at'] as $kolom) {
                if (Schema::hasColumn('pbb_split_items', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
