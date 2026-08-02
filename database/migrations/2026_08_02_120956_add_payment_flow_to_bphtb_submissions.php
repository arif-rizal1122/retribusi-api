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
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            // Data wajib pajak (WP) yang dibayarkan — diinput PPAT saat submit
            $table->string('nik_wp', 16)->nullable()->after('nop');
            $table->string('nama_wp', 255)->nullable()->after('nik_wp');
            $table->text('alamat_wp')->nullable()->after('nama_wp');
            // Status pembayaran & relasi Bill
            $table->string('status', 20)->default('submitted')->after('billing_code');
            $table->unsignedBigInteger('bill_id')->nullable()->after('status');
            $table->timestamp('paid_at')->nullable()->after('bill_id');
            $table->string('ntpd', 64)->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->dropColumn(['nik_wp', 'nama_wp', 'alamat_wp', 'status', 'bill_id', 'paid_at', 'ntpd']);
        });
    }
};
