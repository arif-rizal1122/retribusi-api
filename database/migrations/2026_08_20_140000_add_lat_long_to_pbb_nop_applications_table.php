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
        Schema::table('pbb_nop_applications', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address')->comment('Latitude titik lokasi objek dari map picker');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude')->comment('Longitude titik lokasi objek dari map picker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbb_nop_applications', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
