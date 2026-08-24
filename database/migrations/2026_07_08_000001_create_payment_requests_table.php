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
            $table->foreignId('bill_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_object_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('taxpayer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_channel', 50);
            $table->string('method', 50);
            $table->string('va_number')->nullable()->unique();
            $table->text('qr_content')->nullable();
            $table->decimal('amount_snapshot', 15, 2);
            $table->decimal('admin_fee_snapshot', 15, 2)->default(0);
            $table->decimal('penalty_snapshot', 15, 2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('external_id')->nullable()->unique();
            $table->string('provider_reference')->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_request_safe')->nullable();
            $table->json('raw_response_safe')->nullable();
            $table->timestamps();

            $table->index(['bill_id', 'status']);
            $table->index(['payment_channel', 'method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
