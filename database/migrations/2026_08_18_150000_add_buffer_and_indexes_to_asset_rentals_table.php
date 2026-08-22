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
        Schema::table('asset_rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_rentals', 'buffer_hari')) {
                $table->unsignedSmallInteger('buffer_hari')->default(1)->after('lama_sewa')
                    ->comment('Jeda hari mobilisasi/pemeliharaan sebelum dan sesudah rental');
            }

            // Compound index untuk query ketersediaan kalender berkecepatan tinggi
            $table->index(['asset_item_id', 'status', 'tanggal_mulai', 'tanggal_selesai'], 'idx_rentals_availability_lookup');
        });

        Schema::table('asset_items', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_items', 'buffer_default')) {
                $table->unsignedSmallInteger('buffer_default')->default(1)->after('satuan_tarif')
                    ->comment('Default jeda hari mobilisasi/pemeliharaan unit aset');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            if (Schema::hasColumn('asset_rentals', 'buffer_hari')) {
                $table->dropIndex('idx_rentals_availability_lookup');
                $table->dropColumn('buffer_hari');
            }
        });

        Schema::table('asset_items', function (Blueprint $table) {
            if (Schema::hasColumn('asset_items', 'buffer_default')) {
                $table->dropColumn('buffer_default');
            }
        });
    }
};
