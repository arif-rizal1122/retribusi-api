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
            $table->date('installation_date')->nullable();
            $table->string('last_photo_url')->nullable();
            $table->boolean('is_verified_physically')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_objects', function (Blueprint $table) {
            $table->dropColumn(['installation_date', 'last_photo_url', 'is_verified_physically']);
        });
    }
};
