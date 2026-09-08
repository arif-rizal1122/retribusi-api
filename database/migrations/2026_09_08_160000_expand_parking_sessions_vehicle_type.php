<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->string('vehicle_type', 30)->default('r2')->change();
        });
    }

    public function down(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->string('vehicle_type', 10)->default('r2')->change();
        });
    }
};
