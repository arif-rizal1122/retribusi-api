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
        Schema::table('tax_objects', function (Blueprint $table) {
            if (!Schema::hasColumn('tax_objects', 'tipe_penghuni')) {
                $table->enum('tipe_penghuni', ['PEMILIK', 'PENYEWA'])->default('PEMILIK')->after('name');
            }
            if (!Schema::hasColumn('tax_objects', 'status_hunian')) {
                $table->enum('status_hunian', ['BERPENGHUNI', 'KOSONG'])->default('BERPENGHUNI')->after('tipe_penghuni');
            }
            if (!Schema::hasColumn('tax_objects', 'building_category')) {
                $table->string('building_category', 50)->nullable()->default('RUMAH_SEDERHANA')->after('status_hunian');
            }
            if (!Schema::hasColumn('tax_objects', 'penanggung_jawab_nama')) {
                $table->string('penanggung_jawab_nama')->nullable()->after('building_category');
            }
            if (!Schema::hasColumn('tax_objects', 'penanggung_jawab_kontak')) {
                $table->string('penanggung_jawab_kontak', 50)->nullable()->after('penanggung_jawab_nama');
            }
            if (!Schema::hasColumn('tax_objects', 'land_size_m2')) {
                $table->decimal('land_size_m2', 10, 2)->nullable()->after('penanggung_jawab_kontak');
            }
            if (!Schema::hasColumn('tax_objects', 'building_size_m2')) {
                $table->decimal('building_size_m2', 10, 2)->nullable()->after('land_size_m2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_objects', function (Blueprint $table) {
            $cols = [
                'tipe_penghuni',
                'status_hunian',
                'building_category',
                'penanggung_jawab_nama',
                'penanggung_jawab_kontak',
                'land_size_m2',
                'building_size_m2',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('tax_objects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
