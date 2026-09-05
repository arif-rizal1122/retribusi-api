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
        // 1. Tambahkan field H2H ATR/BPN pada tabel master taxpayer_pbb_objects
        Schema::table('taxpayer_pbb_objects', function (Blueprint $table) {
            if (! Schema::hasColumn('taxpayer_pbb_objects', 'nib_code')) {
                $table->string('nib_code', 20)->nullable()->index()->after('nop')->comment('Nomor Identifikasi Bidang ATR/BPN');
            }
            if (! Schema::hasColumn('taxpayer_pbb_objects', 'znt_zone_code')) {
                $table->string('znt_zone_code', 10)->nullable()->after('nib_code')->comment('Kode Zona Nilai Tanah BPN (contoh: AA, AB)');
            }
            if (! Schema::hasColumn('taxpayer_pbb_objects', 'nir_value')) {
                $table->decimal('nir_value', 15, 2)->nullable()->after('znt_zone_code')->comment('Nilai Indikasi Rata-rata per m2 dari ZNT');
            }
            if (! Schema::hasColumn('taxpayer_pbb_objects', 'polygon_geom')) {
                $table->geometry('polygon_geom', 'polygon', 4326)->nullable()->after('nir_value')->comment('Batas geometri polygon bidang tanah resmi BPN (EPSG:4326)');
            }
        });

        // 2. Tambahkan field penampung permohonan objek PBB baru di pbb_nop_applications
        Schema::table('pbb_nop_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('pbb_nop_applications', 'nib')) {
                $table->string('nib', 20)->nullable()->index()->after('nik')->comment('NIB BPN hasil auto-pull atau manual');
            }
            if (! Schema::hasColumn('pbb_nop_applications', 'znt_applied')) {
                $table->string('znt_applied', 10)->nullable()->after('nib')->comment('Kode Zona ZNT yang diterapkan');
            }
            if (! Schema::hasColumn('pbb_nop_applications', 'nir_applied')) {
                $table->decimal('nir_applied', 15, 2)->nullable()->after('znt_applied')->comment('Nilai Indikasi Rata-rata (Rp/m2) yang diterapkan');
            }
            if (! Schema::hasColumn('pbb_nop_applications', 'polygon_coordinates')) {
                $table->json('polygon_coordinates')->nullable()->after('nir_applied')->comment('Array koordinat polygon dari MapPicker frontend');
            }
            if (! Schema::hasColumn('pbb_nop_applications', 'is_bpn_verified')) {
                $table->boolean('is_bpn_verified')->default(false)->after('polygon_coordinates')->comment('Flag verifikasi auto-pull dari H2H BHUMI BPN');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbb_nop_applications', function (Blueprint $table) {
            $columns = [];
            foreach (['nib', 'znt_applied', 'nir_applied', 'polygon_coordinates', 'is_bpn_verified'] as $col) {
                if (Schema::hasColumn('pbb_nop_applications', $col)) {
                    $columns[] = $col;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('taxpayer_pbb_objects', function (Blueprint $table) {
            $columns = [];
            foreach (['nib_code', 'znt_zone_code', 'nir_value', 'polygon_geom'] as $col) {
                if (Schema::hasColumn('taxpayer_pbb_objects', $col)) {
                    $columns[] = $col;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
