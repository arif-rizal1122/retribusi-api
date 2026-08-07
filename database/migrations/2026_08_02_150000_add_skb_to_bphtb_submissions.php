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
            // SKB digital (F4.1) — Surat Keterangan Bebas
            $table->string('skb_number', 64)->nullable()->unique()->after('ntpd');
            $table->timestamp('skb_issued_at')->nullable()->after('skb_number');
            $table->unsignedBigInteger('skb_issued_by')->nullable()->after('skb_issued_at');
            $table->string('skb_qr_hash', 64)->nullable()->after('skb_issued_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bphtb_submissions', function (Blueprint $table) {
            $table->dropColumn(['skb_number', 'skb_issued_at', 'skb_issued_by', 'skb_qr_hash']);
        });
    }
};
