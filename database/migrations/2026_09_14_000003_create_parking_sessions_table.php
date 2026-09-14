<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaksi Quick-Tap per kendaraan/kapal (r2/r4/truk_bus/inap_truk/proxy_gt_1..4).
     */
    public function up(): void
    {
        Schema::create('parking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_location_id')->constrained('parking_locations')->onDelete('cascade');
            $table->foreignId('jukir_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('parking_shifts')->nullOnDelete();
            $table->date('shift_date');
            $table->string('vehicle_type'); // r2 | r4 | truk_bus | inap_truk | proxy_gt_1..4
            $table->string('plate_hint')->nullable();
            $table->unsignedInteger('duration_days')->default(1);
            $table->decimal('amount', 15, 2);
            $table->string('payment_method'); // cash | qris
            $table->string('qris_reference')->nullable();
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->string('status')->default('completed'); // completed | voided
            $table->timestamps();

            $table->index(['jukir_user_id', 'shift_date']);
            $table->index(['parking_location_id', 'shift_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_sessions');
    }
};