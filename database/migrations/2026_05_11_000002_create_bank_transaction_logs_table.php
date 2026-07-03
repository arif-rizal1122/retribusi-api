<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_config_id')->constrained('bank_configs')->onDelete('cascade');
            $table->foreignId('bill_id')->nullable()->constrained('bills')->onDelete('set null');
            $table->enum('tipe', ['inquiry', 'payment', 'reversal', 'reconcile']);
            $table->json('request_payload');
            $table->json('response_payload')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->string('reference_number', 100)->nullable()->comment('NTB/NTPD/RRN');
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('waktu_request')->useCurrent();
            $table->timestamp('waktu_response')->nullable();
            $table->timestamps();

            $table->index(['bank_config_id', 'tipe']);
            $table->index('reference_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transaction_logs');
    }
};