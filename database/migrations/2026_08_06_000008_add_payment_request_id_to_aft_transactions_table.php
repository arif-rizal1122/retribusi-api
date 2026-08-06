<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aft_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_request_id')->nullable()->after('payment_id');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->onDelete('set null');
            $table->unique('payment_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('aft_transactions', function (Blueprint $table) {
            $table->dropUnique(['payment_request_id']);
            $table->dropForeign(['payment_request_id']);
            $table->dropColumn('payment_request_id');
        });
    }
};
