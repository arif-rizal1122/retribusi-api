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
            $table->decimal('penalty_amount', 15, 2)->default(0)->after('amount');
            $table->string('penalty_type')->default('stpd')->after('penalty_amount'); // stpd, skpdkb, jabatan, angsuran
            $table->decimal('fixed_fine_amount', 15, 2)->default(0)->after('penalty_type'); // e.g., Rp 100,000 for no SPTPD
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['penalty_amount', 'penalty_type', 'fixed_fine_amount']);
        });
    }
};
