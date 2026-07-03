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
        Schema::table('enforcement_notices', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('notes');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
            $table->string('photo_path')->nullable()->after('lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enforcement_notices', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'photo_path']);
        });
    }
};
