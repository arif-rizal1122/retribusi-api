<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aft_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('taxpayer_id');
            $table->decimal('transaction_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('beneficiary_account')->nullable();
            $table->string('beneficiary_bank')->nullable();
            $table->string('status')->default('pending'); // pending | settled | failed
            $table->timestamp('settled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->foreign('taxpayer_id')->references('id')->on('taxpayers')->onDelete('cascade');
            $table->index('status');
            $table->index('taxpayer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aft_transactions');
    }
};
