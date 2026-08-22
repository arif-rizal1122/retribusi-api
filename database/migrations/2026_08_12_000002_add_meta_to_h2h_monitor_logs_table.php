<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom meta (JSON) untuk flag notifikasi anti-spam
     * (mis. {"notified": true, "notified_at": "..."}).
     */
    public function up(): void
    {
        Schema::table('h2h_monitor_logs', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('endpoint');
        });
    }

    public function down(): void
    {
        Schema::table('h2h_monitor_logs', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
