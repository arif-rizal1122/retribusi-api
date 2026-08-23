<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BPHTB-6: Tambah kolom detail objek pajak + digitalisasi dokumen syarat.
     */
    public function up(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->decimal('luas_tanah', 12, 2)->nullable()->after('alamat_wp');
            $table->decimal('luas_bangunan', 12, 2)->nullable()->after('luas_tanah');
            $table->decimal('njop_per_m2', 14, 2)->nullable()->after('luas_bangunan');
            $table->string('alamat_objek')->nullable()->after('njop_per_m2');
            $table->string('jenis_perolehan')->nullable()->after('alamat_objek');
            $table->string('no_sertifikat')->nullable()->after('jenis_perolehan');
        });

        // Data: perbarui syarat retribusi classification id=187 (BPHTB Jual Beli) menjadi 11 dokumen.
        DB::table('retribution_classifications')
            ->where('id', 187)
            ->update([
                'requirements' => json_encode([
                    'ajb', 'ktp', 'npwp', 'sppt', 'akta', 'sk',
                    'foto_objek', 'sketsa_lokasi', 'kwitansi', 'surat_ukur', 'bukti_bayar_pbb',
                ]),
            ]);
    }

    public function down(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'luas_tanah', 'luas_bangunan', 'njop_per_m2',
                'alamat_objek', 'jenis_perolehan', 'no_sertifikat',
            ]);
        });
    }
};
