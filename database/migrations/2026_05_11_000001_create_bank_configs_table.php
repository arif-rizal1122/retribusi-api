<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_configs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bank', 20)->unique()->comment('Kode unik, contoh: MANDIRI, BRI, BNI, BSI, SULTRA');
            $table->string('nama_bank', 100);
            $table->string('nama_singkat', 50);
            $table->string('tipe_driver', 50)->comment('Nama driver class: sultra, mandiri, bri, bni, bsi, qris');
            $table->string('api_endpoint')->nullable()->comment('Base URL API bank');
            $table->string('auth_type', 20)->default('hmac')->comment('hmac|oauth2|api_key|basic');
            
            // Credentials (akan diisi nanti saat sandbox/prod)
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('hmac_secret')->nullable();
            
            $table->json('allowed_ips')->nullable()->comment('Whitelist IP bank');
            $table->string('kode_va_prefix', 10)->nullable()->comment('Prefix VA, contoh: 99, 88000');
            $table->decimal('fee_persen', 5, 2)->default(0)->comment('Fee bank dalam persen');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sandbox')->default(true)->comment('true = pakai mock response');
            $table->json('metadata')->nullable()->comment('Extra config spesifik bank');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_configs');
    }
};