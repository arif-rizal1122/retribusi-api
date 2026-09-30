<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. UPDATE jukir_profiles (Skema Bagi Hasil 40:10:50, BPJS TK, & Rekening Bank)
        Schema::table('jukir_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('jukir_profiles', 'korlap_id')) {
                $table->unsignedBigInteger('korlap_id')->nullable()->after('user_id')->comment('FK users — Koordinator Lapangan');
                $table->foreign('korlap_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('jukir_profiles', 'assigned_spot_id')) {
                $table->ulid('assigned_spot_id')->nullable()->after('assigned_location_id')->comment('FK parking_spots');
            }
            if (!Schema::hasColumn('jukir_profiles', 'revenue_share_pct')) {
                $table->decimal('revenue_share_pct', 5, 2)->default(40.00)->after('split_jukir_percent')->comment('Hak bagi hasil resmi jukir (40%)');
            }
            if (!Schema::hasColumn('jukir_profiles', 'bpjs_tk_number')) {
                $table->string('bpjs_tk_number', 30)->nullable()->after('revenue_share_pct')->comment('Nomor Kartu BPJS Ketenagakerjaan');
            }
            if (!Schema::hasColumn('jukir_profiles', 'bpjs_tk_status')) {
                $table->enum('bpjs_tk_status', ['ACTIVE', 'INACTIVE', 'PENDING'])->default('PENDING')->after('bpjs_tk_number');
            }
            if (!Schema::hasColumn('jukir_profiles', 'verification_status')) {
                $table->enum('verification_status', ['PENDING_SURVEY', 'APPROVED', 'REJECTED'])->default('PENDING_SURVEY')->after('status');
            }
            if (!Schema::hasColumn('jukir_profiles', 'bank_name')) {
                $table->string('bank_name', 50)->nullable()->after('qr_auth_token')->comment('Bank Persepsi / Penyalur (Bank Sultra, BRI, dll)');
            }
            if (!Schema::hasColumn('jukir_profiles', 'bank_account_number')) {
                $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('jukir_profiles', 'bank_account_holder_name')) {
                $table->string('bank_account_holder_name', 100)->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('jukir_profiles', 'is_bank_account_verified')) {
                $table->boolean('is_bank_account_verified')->default(false)->after('bank_account_holder_name');
            }
            if (!Schema::hasColumn('jukir_profiles', 'assigned_polygon')) {
                $table->geometry('assigned_polygon', 'polygon', 4326)->nullable()->after('is_bank_account_verified');
            }
            if (!Schema::hasColumn('jukir_profiles', 'is_geofence_locked')) {
                $table->boolean('is_geofence_locked')->default(true)->after('assigned_polygon');
            }
        });

        // 2. UPDATE jukir_wallets (Manajemen Shift & Handshake QR Serah Terima)
        Schema::table('jukir_wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('jukir_wallets', 'total_cash_collected')) {
                $table->decimal('total_cash_collected', 12, 2)->default(0)->after('total_cash');
            }
            if (!Schema::hasColumn('jukir_wallets', 'total_qris_collected')) {
                $table->decimal('total_qris_collected', 12, 2)->default(0)->after('total_qris');
            }
            if (!Schema::hasColumn('jukir_wallets', 'jukir_share_amount')) {
                $table->decimal('jukir_share_amount', 12, 2)->default(0)->after('total_qris_collected')->comment('Hak jukir 40%');
            }
            if (!Schema::hasColumn('jukir_wallets', 'korlap_share_amount')) {
                $table->decimal('korlap_share_amount', 12, 2)->default(0)->after('jukir_share_amount')->comment('Hak korlap 10%');
            }
            if (!Schema::hasColumn('jukir_wallets', 'net_kasda_due')) {
                $table->decimal('net_kasda_due', 12, 2)->default(0)->after('korlap_share_amount')->comment('Kewajiban setor ke Kasda 50%');
            }
            if (!Schema::hasColumn('jukir_wallets', 'settlement_status')) {
                $table->enum('settlement_status', ['OPEN', 'CLOSED_PENDING_KORLAP', 'RECEIVED_BY_KORLAP', 'SETTLED_TO_KASDA'])
                      ->default('OPEN')->after('shift_status');
            }
            if (!Schema::hasColumn('jukir_wallets', 'handshake_qr_token')) {
                $table->string('handshake_qr_token', 64)->nullable()->unique()->after('settlement_status')->comment('Token QR serah terima fisik');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jukir_wallets', function (Blueprint $table) {
            $table->dropColumn([
                'total_cash_collected',
                'total_qris_collected',
                'jukir_share_amount',
                'korlap_share_amount',
                'net_kasda_due',
                'settlement_status',
                'handshake_qr_token',
            ]);
        });

        Schema::table('jukir_profiles', function (Blueprint $table) {
            $table->dropForeign(['korlap_id']);
            $table->dropColumn([
                'korlap_id',
                'assigned_spot_id',
                'revenue_share_pct',
                'bpjs_tk_number',
                'bpjs_tk_status',
                'verification_status',
                'bank_name',
                'bank_account_number',
                'bank_account_holder_name',
                'is_bank_account_verified',
                'assigned_polygon',
                'is_geofence_locked',
            ]);
        });
    }
};
