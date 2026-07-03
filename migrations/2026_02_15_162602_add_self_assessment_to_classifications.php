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
        Schema::table('retribution_classifications', function (Blueprint $blueprint) {
            $blueprint->boolean('is_self_assessment')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retribution_classifications', function (Blueprint $blueprint) {
            $blueprint->dropColumn('is_self_assessment');
        });
    }
};
