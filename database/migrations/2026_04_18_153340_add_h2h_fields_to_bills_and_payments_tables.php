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
        Schema::table('bills', function (Blueprint $table) {
            $table->string('bank_code')->nullable()->after('status'); // SULTRA, MANDIRI, etc.
            $table->timestamp('expiry_time')->nullable()->after('due_date'); // VA/QRIS expiry
            $table->decimal('penalty_at_payment', 15, 2)->nullable()->after('penalty_amount'); // Snapshot penalty at time of H2H payment
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('transaction_id'); // NTB (Bank)
            $table->string('receipt_number')->nullable()->after('reference_number'); // NTPD (Daerah)
            $table->string('channel')->nullable()->after('payment_method'); // Teller, ATM, Mobile
            $table->json('raw_callback_data')->nullable()->after('channel'); // Audit trail
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['bank_code', 'expiry_time', 'penalty_at_payment']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'receipt_number', 'channel', 'raw_callback_data']);
        });
    }
};
