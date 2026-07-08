<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === 1. AUTO DEDUCT LOGS ===
        Schema::create('auto_deduct_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_log_id')->nullable();
            $table->unsignedBigInteger('taxpayer_id')->nullable();
            $table->unsignedBigInteger('tax_object_id')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->string('source', 50)->default('pos')->comment('pos, qris, va, transfer, manual');
            $table->string('transaction_type', 50)->default('sale')->comment('sale, payment, refund, void, adjustment');
            $table->string('correction_type', 20)->nullable()->comment('void, refund, price_adjustment');
            $table->decimal('transaction_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('deducted_amount', 15, 2)->default(0);
            $table->decimal('original_transaction_amount', 15, 2)->nullable();
            $table->decimal('corrected_transaction_amount', 15, 2)->nullable();
            $table->decimal('tax_recalculated', 15, 2)->nullable();
            $table->decimal('tax_difference', 15, 2)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending, success, failed, voided, adjusted');
            $table->text('correction_note')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedBigInteger('corrected_by_user_id')->nullable();
            $table->string('reversal_reference', 100)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->foreign('parent_log_id')->references('id')->on('auto_deduct_logs')->nullOnDelete();
            $table->index('taxpayer_id');
            $table->index('tax_object_id');
            $table->index('bill_id');
            $table->index('reference_number');
            $table->index('status');
            $table->index('source');
            $table->index('correction_type');
            $table->index('processed_at');
        });

        // === 2. NOTIFICATION LOGS ===
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('taxpayer_id')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('channel', 20)->default('whatsapp');
            $table->string('type', 50)->comment('bill_reminder, payment_receipt, penalty_warning, auto_deduct_receipt, correction_notice');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('message_preview')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('taxpayer_id');
            $table->index('type');
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
            $table->index('sent_at');
        });

        // === 3. CORRECTION LOGS ===
        Schema::create('correction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tax_transaction_id')->nullable();
            $table->unsignedBigInteger('auto_deduct_log_id')->nullable();
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('taxpayer_id')->nullable();
            $table->unsignedBigInteger('tax_object_id')->nullable();
            $table->string('type', 30)->comment('void, refund, price_adjustment, reversal, correction');
            $table->string('trigger', 30)->default('manual');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('new_amount', 15, 2);
            $table->decimal('original_tax', 15, 2)->default(0);
            $table->decimal('new_tax', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->json('details')->nullable();
            $table->string('status', 20)->default('pending');
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
        Schema::dropIfExists('auto_deduct_logs');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('correction_logs');
    }
};
