<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Merge modul AFT ke ledger produksi `auto_deduct_logs`.
 *
 * Sebelumnya pemotongan AFT dicatat pada tabel `aft_transactions` yang merupakan
 * ledger paralel dari `auto_deduct_logs` (dua sumber kebenaran → risiko double-count).
 * Migrasi ini:
 *  1. Menambahkan kolom AFT ke `auto_deduct_logs`.
 *  2. Memindahkan data lama dari `aft_transactions` (jika ada).
 *  3. Menghapus tabel `aft_transactions`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_deduct_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('auto_deduct_logs', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('bill_id');
                $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            }

            if (!Schema::hasColumn('auto_deduct_logs', 'payment_request_id')) {
                $table->unsignedBigInteger('payment_request_id')->nullable()->after('payment_id');
                $table->foreign('payment_request_id')->references('id')->on('payment_requests')->onDelete('set null');
                $table->unique('payment_request_id');
            }

            if (!Schema::hasColumn('auto_deduct_logs', 'beneficiary_account')) {
                $table->string('beneficiary_account', 50)->nullable()->after('deducted_amount');
            }

            if (!Schema::hasColumn('auto_deduct_logs', 'beneficiary_bank')) {
                $table->string('beneficiary_bank', 100)->nullable()->after('beneficiary_account');
            }

            if (!Schema::hasColumn('auto_deduct_logs', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('escrow_settlement_status');
            }
        });

        // Pindahkan data lama dari tabel paralel (jika ada), lalu hapus tabel.
        if (Schema::hasTable('aft_transactions')) {
            $rows = DB::table('aft_transactions')->get();

            foreach ($rows as $row) {
                $legacyStatus = $row->status;
                // Vocab ledger produksi: pending|success|failed|voided|adjusted.
                // Legacy AFT memakai 'settled' → dipetakan ke 'success'.
                $status = $legacyStatus === 'settled' ? 'success' : $legacyStatus;

                DB::table('auto_deduct_logs')->insert([
                    'payment_id' => $row->payment_id,
                    'payment_request_id' => $row->payment_request_id,
                    'taxpayer_id' => $row->taxpayer_id,
                    'source' => $row->metadata ? json_decode((string) $row->metadata, true)['source'] ?? 'pos' : 'pos',
                    'transaction_type' => 'payment',
                    'transaction_amount' => $row->transaction_amount,
                    'tax_amount' => $row->tax_amount,
                    'deducted_amount' => $row->tax_amount,
                    'beneficiary_account' => $row->beneficiary_account,
                    'beneficiary_bank' => $row->beneficiary_bank,
                    'status' => $status,
                    'escrow_settlement_status' => $status,
                    'settled_at' => $row->settled_at,
                    'processed_at' => $row->settled_at,
                    'metadata' => $row->metadata,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::dropIfExists('aft_transactions');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('aft_transactions')) {
            Schema::create('aft_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->unsignedBigInteger('payment_request_id')->nullable();
                $table->unsignedBigInteger('taxpayer_id');
                $table->decimal('transaction_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->string('beneficiary_account')->nullable();
                $table->string('beneficiary_bank')->nullable();
                $table->string('status')->default('pending');
                $table->timestamp('settled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
                $table->foreign('payment_request_id')->references('id')->on('payment_requests')->onDelete('set null');
                $table->index('status');
                $table->index('taxpayer_id');
            });
        }

        Schema::table('auto_deduct_logs', function (Blueprint $table) {
            $table->dropUnique(['payment_request_id']);
            $table->dropForeign(['payment_request_id']);
            $table->dropForeign(['payment_id']);
            $table->dropColumn([
                'payment_id',
                'payment_request_id',
                'beneficiary_account',
                'beneficiary_bank',
                'settled_at',
            ]);
        });
    }
};
