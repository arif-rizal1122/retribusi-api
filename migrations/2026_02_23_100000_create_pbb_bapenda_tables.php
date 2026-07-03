<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxpayer_pbb_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxpayer_id')->constrained('taxpayers')->cascadeOnDelete();
            $table->string('nop', 25);
            $table->string('name_on_sppt', 255)->nullable();
            $table->string('address_on_sppt', 255)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kota', 100)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique(['taxpayer_id', 'nop']);
            $table->index('nop');
        });

        Schema::create('transaction_pbb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('taxpayer_id')->nullable()->constrained('taxpayers')->nullOnDelete();
            $table->string('nop', 25);
            $table->string('tahun', 4);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('denda', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->string('ntpd', 50)->nullable();
            $table->enum('payment_status', ['pending', 'success', 'failed', 'reversed'])->default('pending');
            $table->string('wp_name', 100)->nullable();
            $table->string('wp_address', 200)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kota', 100)->nullable();
            $table->text('reversal_reason')->nullable();
            $table->json('api_response')->nullable();
            $table->timestamps();

            $table->index(['nop', 'tahun']);
            $table->index('payment_status');
            $table->index('ntpd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_pbb');
        Schema::dropIfExists('taxpayer_pbb_objects');
    }
};
