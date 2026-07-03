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
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->text('calculation_formula')->nullable()->after('requirements');
        });

        Schema::table('retribution_rates', function (Blueprint $table) {
            $table->text('calculation_formula')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->dropColumn('calculation_formula');
        });

        Schema::table('retribution_rates', function (Blueprint $table) {
            $table->dropColumn('calculation_formula');
        });
    }
};
