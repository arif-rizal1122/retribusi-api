<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('market_daily_tickets')) {
            Schema::table('market_daily_tickets', function (Blueprint $table) {
                if (!Schema::hasColumn('market_daily_tickets', 'area_sqm')) {
                    $table->decimal('area_sqm', 8, 2)->nullable()->after('quantity');
                }
                if (!Schema::hasColumn('market_daily_tickets', 'dimensions')) {
                    $table->string('dimensions', 100)->nullable()->after('area_sqm');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('market_daily_tickets')) {
            Schema::table('market_daily_tickets', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('market_daily_tickets', 'dimensions')) {
                    $columns[] = 'dimensions';
                }
                if (Schema::hasColumn('market_daily_tickets', 'area_sqm')) {
                    $columns[] = 'area_sqm';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
