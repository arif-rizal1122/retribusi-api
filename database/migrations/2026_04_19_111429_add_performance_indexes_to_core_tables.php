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
        Schema::table('bills', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('period');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('paid_at');
        });

        Schema::table('tax_objects', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex(['bills_status_created_at_index']);
            $table->dropIndex(['bills_period_index']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payments_paid_at_index']);
        });

        Schema::table('tax_objects', function (Blueprint $table) {
            $table->dropIndex(['tax_objects_latitude_longitude_index']);
        });
    }
};
