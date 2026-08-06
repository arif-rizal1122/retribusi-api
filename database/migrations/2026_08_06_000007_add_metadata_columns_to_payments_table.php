<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tendered_amount')) {
                $table->decimal('tendered_amount', 15, 2)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable()->after('tendered_amount');
            }
            if (!Schema::hasColumn('payments', 'metadata')) {
                $table->json('metadata')->nullable()->after('proof_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['tendered_amount', 'change_amount', 'metadata']);
        });
    }
};
