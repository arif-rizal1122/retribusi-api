<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setoran/kliring batch karcis sampah pasar DLH (akhir bulan).
     * Eksekutor adalah petugas/juru pungut pasar yang menahan sebagian
     * karcis dalam "holding balance" sebelum disetorkan ke kas daerah.
     */
    public function up(): void
    {
        Schema::create('dlh_remittances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('collector_user_id');
            $table->tinyInteger('period_month');
            $table->smallInteger('period_year');
            $table->decimal('remitted_amount', 12, 2)->default(0);
            $table->string('bank_reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('cleared'); // pending | cleared
            $table->timestamp('remitted_at')->nullable();
            $table->timestamps();

            $table->foreign('collector_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['collector_user_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dlh_remittances');
    }
};