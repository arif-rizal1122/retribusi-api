<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sppt_records', function (Blueprint $table) {
            $table->id();

            // ── Identitas & Objek ──
            $table->string('nop', 18)->unique()->index();
            $table->string('taxpayer_name')->nullable();
            $table->string('taxpayer_address')->nullable();
            $table->string('object_address')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('tax_year', 4)->nullable()->index();

            // ── Rincian Fisik ──
            $table->decimal('land_area_m2', 15, 2)->nullable();
            $table->decimal('building_area_m2', 15, 2)->nullable();
            $table->string('land_class')->nullable();
            $table->string('building_class')->nullable();
            $table->decimal('land_njop_per_m2', 15, 2)->nullable();
            $table->decimal('building_njop_per_m2', 15, 2)->nullable();

            // ── Perhitungan ──
            $table->decimal('total_land_njop', 15, 2)->nullable();
            $table->decimal('total_building_njop', 15, 2)->nullable();
            $table->decimal('njoptkp', 15, 2)->default(10000000); // NJOPTKP 10jt
            $table->decimal('taxable_njop', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 4)->default(0.0030); // 0.3% (PBB-P2 M-PAD)
            $table->decimal('tax_amount', 15, 2)->nullable();

            // ── Pembayaran & Status ──
            $table->date('due_date')->nullable();
            $table->json('payment_locations')->nullable();
            $table->string('payment_status', 30)->default('unpaid')->index();

            // ── Rekonsiliasi ──
            $table->enum('data_source', ['bpn_sync', 'manual_input', 'sppt_scan'])->default('manual_input');
            $table->enum('bpn_sync_status', ['synced', 'pending', 'conflict', 'not_found'])->default('pending')->index();
            $table->timestamp('bpn_last_synced_at')->nullable();
            $table->json('conflict_fields')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sppt_records');
    }
};
