<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buku kas deposit Quadruple-Lock jukir (append-only ledger).
     * Saldo = SUM(topup) - SUM(deduction); potongan 70% RKUD tiap transaksi tunai.
     */
    public function up(): void
    {
        Schema::create('parking_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // topup | deduction | refund
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->nullable(); // qris | bank_sultra | cash_dishub | auto
            $table->string('reference')->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_deposits');
    }
};