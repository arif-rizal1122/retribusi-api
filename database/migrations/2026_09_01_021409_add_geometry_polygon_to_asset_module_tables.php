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
        Schema::table('asset_module_tables', function (Blueprint $table) {
            // Drop old string column if exists and add polygon geometry
            if (Schema::hasColumn('asset_module_tables', 'koordinat')) {
                $table->dropColumn('koordinat');
            }
            $table->geometry('polygon_geom', 'polygon', 4326)->nullable()->comment('Polygon batas bidang objek/aset (EPSG:4326)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_module_tables', function (Blueprint $table) {
            $table->dropColumn('polygon_geom');
            $table->string('koordinat')->nullable();
        });
    }
};

