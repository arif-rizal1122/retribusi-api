<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema parity dengan database produksi.
 *
 * Tabel-tabel berikut sudah ada di database produksi (di-manage di luar branch dev)
 * namun belum memiliki migrasi. Dibuat ulang di sini dengan guard hasTable agar
 * fresh install (migrate:fresh) tidak kehilangan tabel tersebut dan live database
 * tidak dijalankan dua kali.
 *
 * Tambahan: kolom pembayaran produksi pada tabel `payments` (channel, bank_config_id,
 * reference_number, receipt_number, raw_callback_data) agar alur Payment Request
 * (petugas & webhook) mengisi kolom yang sama dengan sistem pembayaran lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bank_configs')) {
            Schema::create('bank_configs', function (Blueprint $table) {
                $table->id();
                $table->string('kode_bank')->nullable();
                $table->string('nama_bank')->nullable();
                $table->string('nama_singkat')->nullable();
                $table->string('tipe_driver')->nullable()->comment('bri, qris, bni, dll');
                $table->string('api_endpoint')->nullable();
                $table->string('auth_type')->nullable();
                $table->text('client_id')->nullable();
                $table->text('client_secret')->nullable();
                $table->text('api_key')->nullable();
                $table->text('api_secret')->nullable();
                $table->text('hmac_secret')->nullable();
                $table->text('allowed_ips')->nullable();
                $table->string('kode_va_prefix')->nullable();
                $table->decimal('fee_persen', 8, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_sandbox')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_gateway_logs')) {
            Schema::create('payment_gateway_logs', function (Blueprint $table) {
                $table->id();
                $table->string('bill_number')->nullable();
                $table->string('endpoint')->nullable();
                $table->string('method')->nullable();
                $table->json('payload_in')->nullable();
                $table->json('payload_out')->nullable();
                $table->string('ip_address')->nullable();
                $table->integer('status_code')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('auto_deduct_logs')) {
            Schema::create('auto_deduct_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedBigInteger('parent_log_id')->nullable();
                $table->unsignedBigInteger('taxpayer_id')->nullable();
                $table->unsignedBigInteger('tax_object_id')->nullable();
                $table->unsignedBigInteger('bill_id')->nullable();
                $table->string('source', 50)->default('pos')->comment('pos, qris, va, transfer, manual');
                $table->string('transaction_type', 50)->default('sale')->comment('sale, payment, refund, void, adjustment');
                $table->string('correction_type', 20)->nullable()->comment('void, refund, price_adjustment');
                $table->decimal('transaction_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('deducted_amount', 15, 2)->default(0);
                $table->decimal('original_transaction_amount', 15, 2)->nullable();
                $table->decimal('corrected_transaction_amount', 15, 2)->nullable();
                $table->decimal('tax_recalculated', 15, 2)->nullable();
                $table->decimal('tax_difference', 15, 2)->nullable();
                $table->string('reference_number', 100)->nullable();
                $table->string('payment_channel', 50)->nullable();
                $table->json('metadata')->nullable();
                $table->string('status', 20)->default('pending');
                $table->string('escrow_settlement_status')->nullable();
                $table->text('correction_note')->nullable();
                $table->string('failure_reason')->nullable();
                $table->unsignedBigInteger('corrected_by_user_id')->nullable();
                $table->string('reversal_reference', 100)->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('corrected_at')->nullable();
                $table->timestamps();

                $table->index('parent_log_id');
                $table->index('taxpayer_id');
                $table->index('tax_object_id');
                $table->index('bill_id');
                $table->index('reference_number');
                $table->index('status');
                $table->index('source');
                $table->index('correction_type');
                $table->index('processed_at');
                $table->foreign('parent_log_id')->references('id')->on('auto_deduct_logs')->onDelete('set null');
            });
        }

        // Kolom pembayaran produksi yang dipakai alur Payment Request
        Schema::table('payments', function (Blueprint $table) {
            $hasBankConfig = Schema::hasTable('bank_configs');

            if (!Schema::hasColumn('payments', 'bank_config_id')) {
                $column = $table->unsignedBigInteger('bank_config_id')->nullable();
                if (Schema::hasColumn('payments', 'payment_request_id')) {
                    $column->after('payment_request_id');
                }
                if ($hasBankConfig) {
                    $table->foreign('bank_config_id')->references('id')->on('bank_configs')->onDelete('set null');
                }
            }

            if (!Schema::hasColumn('payments', 'channel')) {
                $table->string('channel', 50)->nullable()->after('payment_method');
            }

            if (!Schema::hasColumn('payments', 'reference_number')) {
                $table->string('reference_number', 100)->nullable()->after('transaction_id');
            }

            if (!Schema::hasColumn('payments', 'receipt_number')) {
                $table->string('receipt_number', 100)->nullable()->after('reference_number');
            }

            if (!Schema::hasColumn('payments', 'raw_callback_data')) {
                $table->json('raw_callback_data')->nullable()->after('channel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['bank_config_id']);
            $table->dropColumn([
                'bank_config_id',
                'channel',
                'reference_number',
                'receipt_number',
                'raw_callback_data',
            ]);
        });

        Schema::dropIfExists('auto_deduct_logs');
        Schema::dropIfExists('payment_gateway_logs');
        Schema::dropIfExists('bank_configs');
    }
};
