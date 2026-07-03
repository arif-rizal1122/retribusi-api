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
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->foreignId('taxpayer_id')->constrained('taxpayers')->onDelete('cascade');
            $table->foreignId('tax_object_id')->constrained('tax_objects')->onDelete('cascade');
            $table->date('transaction_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->string('source')->default('manual')->comment('manual, machine, server, surveillance');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tax_object_id', 'transaction_date']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
    }
};
