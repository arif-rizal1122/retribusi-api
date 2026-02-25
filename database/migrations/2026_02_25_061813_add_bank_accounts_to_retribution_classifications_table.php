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
            $table->json('bank_accounts')->nullable()->after('calculation_formula');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->dropColumn('bank_accounts');
        });
    }
};
