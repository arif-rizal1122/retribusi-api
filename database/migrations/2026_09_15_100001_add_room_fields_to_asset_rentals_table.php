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
            $table->foreignId('room_id')->nullable()->after('asset_item_id')
                ->constrained('rusun_rooms')->nullOnDelete();
            $table->boolean('is_guarantee_paid')->default(false)->after('dp');
            $table->timestamp('guarantee_paid_at')->nullable()->after('is_guarantee_paid');
            $table->string('periode_bulan', 7)->nullable()->after('satuan_sewa')
                ->comment('Format YYYY-MM, untuk tagihan bulanan rusunawa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn([
                'room_id',
                'is_guarantee_paid',
                'guarantee_paid_at',
                'periode_bulan',
            ]);
        });
    }
};
