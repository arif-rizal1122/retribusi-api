<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_transaction_id')->nullable()->after('id');
            $table->string('correction_type', 20)->nullable()->after('source')->comment('void, refund, adjustment');
            $table->decimal('original_amount', 15, 2)->nullable()->after('tax_amount');
            $table->decimal('original_tax_amount', 15, 2)->nullable()->after('original_amount');
            $table->decimal('corrected_amount', 15, 2)->nullable()->after('original_tax_amount');
            $table->decimal('tax_difference', 15, 2)->nullable()->after('corrected_amount')->comment('Selisih pajak setelah koreksi');
            $table->string('correction_reason')->nullable()->after('tax_difference');
            $table->unsignedBigInteger('corrected_by')->nullable()->after('correction_reason');
            $table->timestamp('corrected_at')->nullable()->after('corrected_by');

            $table->foreign('parent_transaction_id')->references('id')->on('tax_transactions')->nullOnDelete();
            $table->index('correction_type');
        });
    }

    public function down(): void
    {
        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->dropForeign(['parent_transaction_id']);
            $table->dropColumn([
                'parent_transaction_id', 'correction_type',
                'original_amount', 'original_tax_amount',
                'corrected_amount', 'tax_difference',
                'correction_reason', 'corrected_by', 'corrected_at',
            ]);
        });
    }
};
