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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bill_id')->nullable()->change();
            $table->string('billing_period', 7)->nullable()->after('bill_id'); // YYYY-MM
            $table->foreignId('taxpayer_id')->nullable()->after('billing_period')->constrained()->onDelete('cascade');
            $table->foreignId('tax_object_id')->nullable()->after('taxpayer_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending')->after('amount'); // pending, success, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['taxpayer_id']);
            $table->dropForeign(['tax_object_id']);
            $table->dropColumn(['billing_period', 'taxpayer_id', 'tax_object_id', 'status']);
            $table->foreignId('bill_id')->nullable(false)->change();
        });
    }
};
