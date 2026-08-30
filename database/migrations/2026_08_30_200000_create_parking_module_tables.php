<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // === 1. PARKING LOCATIONS ===
        Schema::create('parking_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('e.g. PKR-SUDIRMAN-01');
            $table->string('name');
            $table->enum('category', ['retribusi_umum', 'retribusi_khusus', 'pajak_pbjt'])
                  ->default('retribusi_umum')
                  ->comment('retribusi_umum=Dishub Tepi Jalan, retribusi_khusus=Dishub TKP, pajak_pbjt=Bapenda');
            $table->unsignedBigInteger('opd_id');
            $table->unsignedBigInteger('classification_id')->nullable()
                  ->comment('FK retribution_classifications (199=RET-PRK, 205=JU-PRK, 172=PBJT-PRK)');
            $table->decimal('rate_r2', 12, 2)->default(2000)->comment('Tarif kendaraan roda 2');
            $table->decimal('rate_r4', 12, 2)->default(3000)->comment('Tarif kendaraan roda 4');
            $table->text('base_qris_payload')->nullable()->comment('Base QRIS statis ber-NMID, diunduh sekali');
            $table->string('nmid', 20)->nullable()->comment('National Merchant ID 15 digit dari Bank Persepsi');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('opd_id');
            $table->index('classification_id');
            $table->index('category');
            $table->index('is_active');
        });

        // === 2. PARKING SESSIONS ===
        Schema::create('parking_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parking_location_id');
            $table->unsignedBigInteger('jukir_user_id')->comment('FK users — petugas jukir yang mencatat');
            $table->date('shift_date')->comment('Tanggal shift');
            $table->enum('vehicle_type', ['r2', 'r4'])->comment('r2=roda 2, r4=roda 4');
            $table->string('plate_hint', 10)->nullable()->comment('3-4 karakter akhir plat (opsional)');
            $table->decimal('amount', 12, 2)->comment('Nominal retribusi yang dibayar');
            $table->enum('payment_method', ['cash', 'qris'])->default('cash');
            $table->string('qris_reference', 20)->nullable()->comment('Counter lokal MM-XXXX dari referralCounterService');
            $table->unsignedBigInteger('bill_id')->nullable()->comment('FK bills — auto-generated billing record');
            $table->enum('status', ['completed', 'voided'])->default('completed');
            $table->timestamps();

            $table->foreign('parking_location_id')->references('id')->on('parking_locations')->cascadeOnDelete();
            $table->index('jukir_user_id');
            $table->index('bill_id');
            $table->index('shift_date');
            $table->index('vehicle_type');
            $table->index('payment_method');
            $table->index('status');
            $table->index(['jukir_user_id', 'shift_date']);
        });

        // === 3. JUKIR WALLETS (Rekap Shift Harian) ===
        Schema::create('jukir_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('FK users — 1 wallet per jukir');
            $table->unsignedBigInteger('parking_location_id')->nullable()->comment('Titik yang sedang ditugaskan');
            $table->date('shift_date')->comment('Tanggal shift');
            $table->enum('shift_status', ['open', 'closed'])->default('open');
            $table->timestamp('shift_opened_at')->nullable();
            $table->timestamp('shift_closed_at')->nullable();
            $table->decimal('total_cash', 15, 2)->default(0)->comment('Akumulasi transaksi tunai shift ini');
            $table->decimal('total_qris', 15, 2)->default(0)->comment('Akumulasi transaksi QRIS shift ini');
            $table->integer('total_sessions')->default(0)->comment('Jumlah kendaraan shift ini');
            $table->boolean('settled')->default(false)->comment('Sudah disetor ke bendahara');
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('settled_by')->nullable()->comment('Admin Dishub yang verifikasi setoran');
            $table->timestamps();

            $table->index('user_id');
            $table->index('parking_location_id');
            $table->index('shift_date');
            $table->index('shift_status');
            $table->index('settled');
            $table->unique(['user_id', 'shift_date'], 'jukir_wallets_user_date_unique');
        });

        // === 4. FIX OPD ASSIGNMENT ===
        // Perbaiki opd_id untuk klasifikasi retribusi parkir:
        // RET-PRK (199) & JU-PRK (205) seharusnya Dishub (opd_id=2), bukan Bapenda (5)
        DB::table('retribution_classifications')
            ->whereIn('id', [199, 205])
            ->update(['opd_id' => 2]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jukir_wallets');
        Schema::dropIfExists('parking_sessions');
        Schema::dropIfExists('parking_locations');

        // Kembalikan opd_id ke Bapenda (5)
        DB::table('retribution_classifications')
            ->whereIn('id', [199, 205])
            ->update(['opd_id' => 5]);
    }
};
