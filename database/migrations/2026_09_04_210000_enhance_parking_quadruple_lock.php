<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. JUKIR PROFILES (Data Surat Tugas, Deposit, & Bagi Hasil)
        if (!Schema::hasTable('jukir_profiles')) {
            Schema::create('jukir_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique()->comment('FK users');
                $table->string('nik', 16)->nullable()->comment('NIK Terverifikasi Dukcapil');
                $table->string('surat_tugas_no', 50)->comment('Nomor ST/SIP Dishub');
                $table->dateTime('surat_tugas_expired_at')->comment('Tenggat waktu izin tugas');
                $table->decimal('deposit_balance', 15, 2)->default(0)->comment('Saldo kuota bayar tunai');
                $table->decimal('split_rkud_percent', 5, 2)->default(70.00)->comment('Porsi Kas Daerah');
                $table->decimal('split_jukir_percent', 5, 2)->default(30.00)->comment('Porsi Wallet Jukir');
                $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
                $table->unsignedBigInteger('assigned_location_id')->nullable()->comment('FK parking_locations');
                $table->string('qr_auth_token', 64)->unique()->nullable()->comment('Token unik QR rompi/ID Card');
                $table->timestamps();

                $table->foreign('assigned_location_id')->references('id')->on('parking_locations')->nullOnDelete();
                $table->index('surat_tugas_expired_at');
                $table->index('status');
            });
        }

        // 2. JUKIR DEPOSIT TRANSACTIONS (Mutasi Saldo Kuota)
        if (!Schema::hasTable('jukir_deposit_transactions')) {
            Schema::create('jukir_deposit_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('jukir_user_id');
                $table->enum('type', ['topup', 'deduction', 'refund'])->comment('topup=beli saldo, deduction=potong saat cash, refund=pengembalian');
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->string('reference_id', 50)->nullable()->comment('Nomor referensi / session_id');
                $table->string('payment_method', 30)->default('qris')->comment('qris, bank_sultra_va, cash_dishub');
                $table->enum('status', ['success', 'pending', 'failed'])->default('success');
                $table->timestamps();

                $table->index('jukir_user_id');
                $table->index('type');
                $table->index('created_at');
            });
        }

        // 3. ENHANCE PARKING SESSIONS (Receipt Token, Splitting, Geolocation)
        Schema::table('parking_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('parking_sessions', 'deposit_deducted')) {
                $table->decimal('deposit_deducted', 12, 2)->default(0)->after('amount')->comment('Nominal dipotong dari deposit jukir (70%)');
            }
            if (!Schema::hasColumn('parking_sessions', 'jukir_earnings')) {
                $table->decimal('jukir_earnings', 12, 2)->default(0)->after('deposit_deducted')->comment('Nominal hak jukir (30%)');
            }
            if (!Schema::hasColumn('parking_sessions', 'receipt_token')) {
                $table->string('receipt_token', 32)->nullable()->unique()->after('status')->comment('Token unik e-Kupon / Struk QR');
            }
            if (!Schema::hasColumn('parking_sessions', 'receipt_claimed')) {
                $table->boolean('receipt_claimed')->default(false)->after('receipt_token')->comment('Sudah di-scan warga untuk poin undian');
            }
            if (!Schema::hasColumn('parking_sessions', 'claimed_by_taxpayer_id')) {
                $table->unsignedBigInteger('claimed_by_taxpayer_id')->nullable()->after('receipt_claimed');
            }
            if (!Schema::hasColumn('parking_sessions', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('claimed_by_taxpayer_id');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        // 4. ENHANCE PARKING LOCATIONS (Geofence & EWS Baseline)
        Schema::table('parking_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('parking_locations', 'geofence_radius_meters')) {
                $table->integer('geofence_radius_meters')->default(100)->after('longitude')->comment('Batas radius jukir boleh scan');
            }
            if (!Schema::hasColumn('parking_locations', 'target_daily_revenue')) {
                $table->decimal('target_daily_revenue', 15, 2)->default(100000)->after('geofence_radius_meters');
            }
            if (!Schema::hasColumn('parking_locations', 'baseline_hourly_sessions')) {
                $table->json('baseline_hourly_sessions')->nullable()->after('target_daily_revenue')->comment('Baseline AI jam sibuk vs sepi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parking_locations', function (Blueprint $table) {
            $table->dropColumn(['geofence_radius_meters', 'target_daily_revenue', 'baseline_hourly_sessions']);
        });

        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->dropColumn(['deposit_deducted', 'jukir_earnings', 'receipt_token', 'receipt_claimed', 'claimed_by_taxpayer_id', 'latitude', 'longitude']);
        });

        Schema::dropIfExists('jukir_deposit_transactions');
        Schema::dropIfExists('jukir_profiles');
    }
};
