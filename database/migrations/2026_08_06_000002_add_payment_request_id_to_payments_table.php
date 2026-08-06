<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_request_id')->nullable()->after('bill_id');
            $table->foreign('payment_request_id')->references('id')->on('payment_requests')->onDelete('set null');
            $table->index('payment_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_request_id']);
            $table->dropIndex(['payment_request_id']);
            $table->dropColumn('payment_request_id');
        });
    }
};
