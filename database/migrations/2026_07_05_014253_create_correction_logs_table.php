<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_transaction_id')->nullable();
            $table->unsignedBigInteger('auto_deduct_log_id')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('taxpayer_id')->nullable();
            $table->unsignedBigInteger('tax_object_id')->nullable();
            $table->string('type', 30)->comment('void, refund, price_adjustment, reversal, correction');
            $table->string('trigger', 30)->default('manual')->comment('manual, automatic, webhook');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('new_amount', 15, 2);
            $table->decimal('original_tax', 15, 2)->default(0);
            $table->decimal('new_tax', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->json('details')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending, applied, rejected');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('tax_transaction_id');
            $table->index('auto_deduct_log_id');
            $table->index('bill_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_logs');
    }
};
