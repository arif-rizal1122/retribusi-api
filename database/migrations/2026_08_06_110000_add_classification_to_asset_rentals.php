<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_rentals', 'retribution_classification_id')) {
                $table->unsignedBigInteger('retribution_classification_id')->nullable()->after('retribution_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->dropColumn('retribution_classification_id');
        });
    }
};
