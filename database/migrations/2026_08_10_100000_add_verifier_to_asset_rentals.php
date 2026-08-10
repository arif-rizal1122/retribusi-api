<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M39-2 — jejak verifikator & approver OPD pengampu pada sewa aset.
     */
    public function up(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->unsignedBigInteger('verified_by_user_id')->nullable()->after('catatan_approval');
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('verified_by_user_id');

            $table->foreign('verified_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('asset_rentals', function (Blueprint $table) {
            $table->dropForeign(['verified_by_user_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn(['verified_by_user_id', 'approved_by_user_id']);
        });
    }
};
