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
            // Komponen Mobilisasi Tronton (Self Loader)
            if (!Schema::hasColumn('asset_rentals', 'include_tronton')) {
                $table->boolean('include_tronton')->default(false)->after('lokasi_penggunaan')->comment('Apakah sewa alat berat menyertakan mobilisasi armada tronton');
            }
            if (!Schema::hasColumn('asset_rentals', 'jarak_tronton_km')) {
                $table->decimal('jarak_tronton_km', 8, 2)->nullable()->after('include_tronton')->comment('Jarak mobilisasi tronton dalam kilometer');
            }
            if (!Schema::hasColumn('asset_rentals', 'biaya_tronton')) {
                $table->decimal('biaya_tronton', 15, 2)->nullable()->after('jarak_tronton_km')->comment('Tarif retribusi sewa tronton sesuai zonasi Perda 1/2024');
            }

            // Rekomendasi & Hasil Survey Lapangan Petugas
            if (!Schema::hasColumn('asset_rentals', 'survey_rekomendasi_tronton')) {
                $table->string('survey_rekomendasi_tronton', 100)->nullable()->after('survey_kesimpulan')->comment('Rekomendasi teknis surveyor: Wajib Tronton / Bisa Jalan Mandiri');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_penjebolan_akses')) {
                $table->boolean('survey_penjebolan_akses')->default(false)->after('survey_rekomendasi_tronton')->comment('Apakah perlu perintisan / penjebolan akses sementara (trotoar/drainase/tebing)');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_penjebolan_catatan')) {
                $table->text('survey_penjebolan_catatan')->nullable()->after('survey_penjebolan_akses')->comment('Detail metode perintisan (plat baja/urukan sirtu) dan komitmen reinstatement');
            }
            if (!Schema::hasColumn('asset_rentals', 'survey_rekomendasi_alat')) {
                $table->string('survey_rekomendasi_alat', 255)->nullable()->after('survey_penjebolan_catatan')->comment('Rekomendasi alat tambahan/pengganti (contoh: Excavator Breaker atau Dump Truck)');
            }
        });

        Schema::table('asset_items', function (Blueprint $table) {
            $table->string('satuan_tarif', 50)->default('per hari')->change();
            if (!Schema::hasColumn('asset_items', 'wajib_tronton')) {
                $table->boolean('wajib_tronton')->default(false)->after('satuan_tarif')->comment('Flag wajib mobilisasi tronton untuk alat bertapak rantai besi / drum silinder');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->dropColumn([
                'include_tronton',
                'jarak_tronton_km',
                'biaya_tronton',
                'survey_rekomendasi_tronton',
                'survey_penjebolan_akses',
                'survey_penjebolan_catatan',
                'survey_rekomendasi_alat',
            ]);
        });

        Schema::table('asset_items', function (Blueprint $table) {
            $table->dropColumn(['wajib_tronton']);
        });
    }
};
