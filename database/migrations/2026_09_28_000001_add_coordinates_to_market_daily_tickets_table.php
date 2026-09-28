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
                if (!Schema::hasColumn('market_daily_tickets', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('market_daily_tickets', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                }
                if (!Schema::hasColumn('market_daily_tickets', 'accuracy')) {
                    $table->decimal('accuracy', 8, 2)->nullable()->after('longitude');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('market_daily_tickets')) {
            Schema::table('market_daily_tickets', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('market_daily_tickets', 'accuracy')) {
                    $columns[] = 'accuracy';
                }
                if (Schema::hasColumn('market_daily_tickets', 'longitude')) {
                    $columns[] = 'longitude';
                }
                if (Schema::hasColumn('market_daily_tickets', 'latitude')) {
                    $columns[] = 'latitude';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
