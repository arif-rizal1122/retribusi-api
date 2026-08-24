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
        Schema::table('auto_deduct_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('id');
            $table->string('escrow_settlement_status')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_deduct_logs', function (Blueprint $table) {
            $table->dropColumn(['city_id', 'escrow_settlement_status']);
        });
    }
};
