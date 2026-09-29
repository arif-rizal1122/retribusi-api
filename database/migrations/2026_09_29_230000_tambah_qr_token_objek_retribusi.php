<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom token QR untuk identitas fisik retribusi daerah (NORD).
 *
 * Stiker QR ditempel pada objek fisik: kios pasar, pos juru parkir, persil
 * sampah, lapak TPI. Token dipakai pada URL pemindaian, BUKAN nomor NORD
 * itu sendiri, supaya nomor tidak mudah dipalsukan dari foto stiker.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tax_objects', 'qr_token')) {
            Schema::table('tax_objects', function (Blueprint $table) {
                $table->string('qr_token', 32)->nullable()->unique()->after('nop');
                $table->timestamp('qr_dicetak_at')->nullable()->after('qr_token');
            });
        }

        if (Schema::hasTable('market_stalls') && ! Schema::hasColumn('market_stalls', 'qr_token')) {
            Schema::table('market_stalls', function (Blueprint $table) {
                $table->string('qr_token', 32)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tax_objects', 'qr_token')) {
            Schema::table('tax_objects', function (Blueprint $table) {
                $table->dropColumn(['qr_token', 'qr_dicetak_at']);
            });
        }
        if (Schema::hasTable('market_stalls') && Schema::hasColumn('market_stalls', 'qr_token')) {
            Schema::table('market_stalls', function (Blueprint $table) {
                $table->dropColumn('qr_token');
            });
        }
    }
};
