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
        Schema::table('retribution_types', function (Blueprint $table) {
            $table->enum('billing_cycle', ['daily', 'weekly', 'monthly', 'yearly'])
                  ->default('monthly')
                  ->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retribution_types', function (Blueprint $table) {
            $table->dropColumn('billing_cycle');
        });
    }
};
