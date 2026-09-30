<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jukir_monthly_claims')) {
            Schema::create('jukir_monthly_claims', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->unsignedBigInteger('jukir_id')->comment('FK users — Juru Parkir penerima hak');
                $table->unsignedSmallInteger('period_month')->comment('Bulan periode (1-12)');
                $table->unsignedSmallInteger('period_year')->comment('Tahun periode (cth: 2026)');
                $table->integer('total_transactions_count')->default(0)->comment('Total transaksi tercatat');
                $table->decimal('total_gross_turnover', 15, 2)->default(0)->comment('Total omzet bruto parkir');
                $table->decimal('cash_turnover', 15, 2)->default(0)->comment('Omzet pembayaran tunai');
                $table->decimal('qris_turnover', 15, 2)->default(0)->comment('Omzet pembayaran cashless/QRIS (masuk RKUD)');
                $table->decimal('jukir_gross_share', 15, 2)->default(0)->comment('Akumulasi hak bruto 40% jukir');
                $table->decimal('cash_already_retained', 15, 2)->default(0)->comment('Pengurang hak (tunai yang sudah diambil langsung)');
                $table->decimal('bpjs_deduction', 15, 2)->default(16800.00)->comment('Potongan iuran BPJS TK (JKK & JKM)');
                $table->decimal('net_payable_amount', 15, 2)->default(0)->comment('Hak bersih transfer bank (Direct RKUD)');
                $table->string('bank_name', 50)->nullable()->comment('Bank tujuan transfer');
                $table->string('account_number', 50)->nullable()->comment('Nomor rekening jukir');
                $table->enum('disbursement_status', ['DRAFT', 'APPROVED_BY_DISHUB', 'PAID_BY_KASDA', 'FAILED'])
                      ->default('DRAFT');
                $table->timestamp('paid_at')->nullable();
                $table->string('bank_reference_number', 100)->nullable();
                $table->timestamps();

                $table->foreign('jukir_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['jukir_id', 'period_month', 'period_year'], 'jukir_monthly_claim_period_unique');
                $table->index('disbursement_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jukir_monthly_claims');
    }
};
