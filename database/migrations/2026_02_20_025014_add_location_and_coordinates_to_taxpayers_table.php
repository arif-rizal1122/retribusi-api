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
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->string('district')->nullable()->after('address');
            $table->string('sub_district')->nullable()->after('district');
            $table->decimal('latitude', 10, 8)->nullable()->after('object_address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->dropColumn(['district', 'sub_district', 'latitude', 'longitude']);
        });
    }
};
