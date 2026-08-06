<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->unsignedBigInteger('taxpayer_id');
            $table->string('method')->default('officer'); // bri_va | qris | officer
            $table->string('provider')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->json('bill_ids')->nullable();
            $table->json('bill_numbers')->nullable();
            $table->string('va_number')->nullable();
            $table->string('qris_string')->nullable();
            $table->text('qr_payload')->nullable();
            $table->string('external_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('instructions')->nullable();
            $table->boolean('can_refresh')->default(true);
            $table->boolean('can_cancel')->default(true);
            $table->json('receipts')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('taxpayer_id')->references('id')->on('taxpayers')->onDelete('cascade');
            $table->index('token');
            $table->index('taxpayer_id');
            $table->index('status');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
