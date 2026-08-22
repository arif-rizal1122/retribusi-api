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
        Schema::table('pbb_nop_applications', function (Blueprint $table) {
            $table->string('registered_for')->default('self')->after('status');
            $table->string('owner_name')->nullable()->after('registered_for');
            $table->string('owner_address')->nullable()->after('owner_name');
            $table->json('metadata')->nullable()->after('owner_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pbb_nop_applications', function (Blueprint $table) {
            $table->dropColumn(['registered_for', 'owner_name', 'owner_address', 'metadata']);
        });
    }
};
