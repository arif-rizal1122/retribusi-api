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
        Schema::create('bphtb_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('nib', 50)->index();
            $table->string('nop', 50)->nullable()->index();
            $table->unsignedBigInteger('ppat_user_id')->nullable();
            $table->decimal('reported_npop', 15, 2); // Nilai transaksi riil PPAT
            $table->decimal('znt_applied', 15, 2)->nullable(); // ZNT BPN
            $table->decimal('final_npop', 15, 2); // NPOP final yang digunakan hitung pajak
            $table->string('status_flag', 50)->default('VALID'); // e.g., VALID, UNDER_ZNT_FLAG
            $table->string('billing_code', 50)->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bphtb_submissions');
    }
};
