<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Karcis sampah pasar harian yang diterbitkan juru pungut DLH di lapangan.
     * Detail holding balance = karcis tanpa remittance_id (belum masuk kliring
     * batch akhir bulan). remittance_id menandai karcis yang sudah tersetor.
     */
    public function up(): void
    {
        Schema::create('dlh_market_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->unsignedBigInteger('collector_user_id');
            $table->string('market_name');
            $table->string('stall_name_or_number')->nullable();
            $table->string('merchant_name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->default('TUNAI'); // TUNAI | QRIS_INSTANT
            $table->string('qr_token')->nullable();
            $table->unsignedBigInteger('remittance_id')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->foreign('collector_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('remittance_id')->references('id')->on('dlh_remittances')->nullOnDelete();
            $table->index(['collector_user_id', 'remittance_id']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlh_market_tickets');
    }
};