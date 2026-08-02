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
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('skb_qr_hash');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            $table->text('verified_notes')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'verified_by', 'verified_notes']);
        });
    }
};
