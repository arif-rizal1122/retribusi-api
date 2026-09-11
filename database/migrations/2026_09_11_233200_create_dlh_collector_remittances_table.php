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
        Schema::create('dlh_collector_remittances', function (Blueprint $table) {
            $table->id();
            $table->string('remittance_number', 50)->unique();
            $table->foreignId('collector_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('opd_id')->default(4)->constrained('opds');
            $table->unsignedSmallInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedInteger('total_tickets_issued')->default(0);
            $table->decimal('total_amount_collected', 15, 2)->default(0);
            $table->decimal('remitted_amount', 15, 2)->default(0);
            $table->date('remittance_date')->nullable();
            $table->string('bank_reference_number')->nullable();
            $table->string('sts_proof_url')->nullable();
            $table->enum('status', [
                'HOLDING',     // Saldo masih dipegang juru pungut
                'SUBMITTED',   // Diserahkan / diajukan setor
                'VERIFIED',    // Diverifikasi bendahara penerimaan DLH
                'RECONCILED'   // Disetor ke RKUD Kasda
            ])->default('HOLDING');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dlh_market_trash_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 50)->unique();
            $table->foreignId('collector_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('remittance_id')->nullable()->constrained('dlh_collector_remittances')->nullOnDelete();
            $table->string('market_name')->default('Pasar Karya Nugraha'); // atau Pasar Sentral, Pasar Wameo
            $table->string('stall_name_or_number')->nullable();
            $table->string('merchant_name')->nullable();
            $table->decimal('amount', 15, 2); // Rp 3.000 atau Rp 6.000
            $table->string('payment_method', 30)->default('TUNAI'); // TUNAI, QRIS_INSTANT
            $table->string('qr_token', 100)->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dlh_market_trash_tickets');
        Schema::dropIfExists('dlh_collector_remittances');
    }
};
