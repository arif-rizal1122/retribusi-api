<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom alur persetujuan & pengembalian sewa alat (admin UPTD).
     * Melengkapi dashboard retribusi-admin AssetRentalsManagement:
     * pending_verification -> verified -> approved -> completed.
     */
    public function up(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->after('verified_by')->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikator')->nullable()->after('approved_by');
            $table->text('catatan_approval')->nullable()->after('catatan_verifikator');
            $table->date('tanggal_pengembalian')->nullable()->after('catatan_approval');
            $table->string('kondisi_pengembalian')->nullable()->after('tanggal_pengembalian');
            $table->decimal('denda', 15, 2)->nullable()->after('kondisi_pengembalian');
        });
    }

    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'verified_by',
                'approved_by',
                'catatan_verifikator',
                'catatan_approval',
                'tanggal_pengembalian',
                'kondisi_pengembalian',
                'denda',
            ]);
        });
    }
};