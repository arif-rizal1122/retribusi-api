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
        // 1. Tambahkan kolom pendukung di signed_documents jika belum ada
        Schema::table('signed_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('signed_documents', 'opd_id')) {
                $table->unsignedBigInteger('opd_id')->nullable()->after('document_id');
            }
            if (!Schema::hasColumn('signed_documents', 'signer_name')) {
                $table->string('signer_name')->nullable()->after('signed_by');
            }
            if (!Schema::hasColumn('signed_documents', 'signer_nip')) {
                $table->string('signer_nip')->nullable()->after('signer_name');
            }
            if (!Schema::hasColumn('signed_documents', 'signer_role')) {
                $table->string('signer_role')->nullable()->after('signer_nip');
            }
            if (!Schema::hasColumn('signed_documents', 'signature_type')) {
                $table->string('signature_type')->default('ELECTRONIC_SEAL')->after('signer_role'); // ELECTRONIC_SEAL, PERSONAL_TTE, NUMERIC_TOKEN_TRANSITION
            }
            if (!Schema::hasColumn('signed_documents', 'sha256_checksum')) {
                $table->char('sha256_checksum', 64)->nullable()->after('signature_hash');
            }
        });

        // 2. Buat tabel tte_logs untuk mencatat audit trail request H2H BSrE
        if (!Schema::hasTable('tte_logs')) {
            Schema::create('tte_logs', function (Blueprint $table) {
                $table->id();
                $table->string('document_number')->index();
                $table->string('endpoint');
                $table->json('request_payload')->nullable();
                $table->integer('response_code')->default(0);
                $table->json('response_body')->nullable();
                $table->integer('duration_ms')->default(0);
                $table->string('status')->default('success'); // success, fallback_local_seal, error
                $table->timestamps();
            });
        }

        // 3. Buat tabel asset_tokens untuk token registrasi aset resmi (solusi transisi TTE Kepala OPD)
        if (!Schema::hasTable('asset_tokens')) {
            Schema::create('asset_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('token_code', 64)->unique()->index();
                $table->string('opd_code', 20)->index();
                $table->unsignedBigInteger('asset_id')->nullable()->index();
                $table->unsignedBigInteger('asset_rental_id')->nullable()->index();
                $table->unsignedBigInteger('bill_id')->nullable()->index();
                $table->string('contract_number', 100)->nullable();
                $table->string('tenant_identity', 100)->nullable();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_tokens');
        Schema::dropIfExists('tte_logs');

        Schema::table('signed_documents', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['opd_id', 'signer_name', 'signer_nip', 'signer_role', 'signature_type', 'sha256_checksum'] as $col) {
                if (Schema::hasColumn('signed_documents', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
