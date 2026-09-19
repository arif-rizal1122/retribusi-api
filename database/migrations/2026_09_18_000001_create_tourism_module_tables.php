<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Pariwisata M-PAD — OPD 16 (Dinas Pariwisata dan Kebudayaan Kota Baubau).
 * Dasar hukum: Perda Kota Baubau No. 1 Tahun 2024 (Pasal 84 ayat 1g & 1i, Pasal 92, Pasal 106).
 *
 * Catatan desain:
 * - Tabel retribution_types TIDAK punya kolom code; identitas sektor lewat category + name.
 * - Kuota harian per destinasi per tanggal disimpan di tourism_quotas.
 * - Diskon/keringanan legal (Pasal 106) dicatat di tourism_legal_waivers agar tarif dasar
 *   Perda tetap terkunci dan diskresi terekam jejak auditnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tourism_destinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opd_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('daily_capacity')->default(0);
            $table->integer('session_quota')->default(0);
            $table->string('open_time', 10)->nullable();
            $table->string('close_time', 10)->nullable();
            $table->boolean('is_open')->default(true);
            $table->boolean('requires_offline_mode')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['opd_id', 'is_active']);
        });

        Schema::create('tourism_quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tourism_destination_id');
            $table->date('quota_date');
            $table->string('session_time', 20)->default('all');
            $table->integer('capacity');
            $table->integer('booked')->default(0);
            $table->integer('checked_in')->default(0);
            $table->timestamps();

            $table->unique(['tourism_destination_id', 'quota_date', 'session_time'], 'tourism_quota_unique');
        });

        Schema::create('tourism_legal_waivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opd_id');
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->unsignedBigInteger('asset_rental_id')->nullable();
            $table->string('legal_basis');
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->string('decree_number')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->enum('status', ['Diajukan', 'Disetujui', 'Ditolak'])->default('Diajukan');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['opd_id', 'status']);
        });

        Schema::create('offline_ticket_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tourism_destination_id');
            $table->string('batch_code')->unique();
            $table->integer('allocated_tokens')->default(0);
            $table->integer('used_tokens')->default(0);
            $table->date('valid_date');
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_synced']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->unsignedBigInteger('tourism_destination_id')->nullable()->after('retribution_classification_id');
            $table->string('ticket_type', 20)->nullable()->after('tourism_destination_id');
            $table->string('visitor_category', 30)->nullable()->after('ticket_type');
            $table->integer('pax_count')->default(1)->after('visitor_category');
            $table->integer('pax_checked_in')->default(0)->after('pax_count');
            $table->date('visit_date')->nullable()->after('pax_checked_in');
            $table->string('session_time', 20)->nullable()->after('visit_date');
            $table->decimal('legal_discount_percent', 5, 2)->default(0)->after('session_time');
            $table->string('legal_waiver_ref')->nullable()->after('legal_discount_percent');
            $table->string('nationality', 3)->nullable()->after('legal_waiver_ref');
            $table->string('passport_number')->nullable()->after('nationality');
            $table->string('master_qr_token')->nullable()->after('passport_number');
            $table->boolean('is_offline_issued')->default(false)->after('master_qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn([
                'tourism_destination_id',
                'ticket_type',
                'visitor_category',
                'pax_count',
                'pax_checked_in',
                'visit_date',
                'session_time',
                'legal_discount_percent',
                'legal_waiver_ref',
                'nationality',
                'passport_number',
                'master_qr_token',
                'is_offline_issued',
            ]);
        });

        Schema::dropIfExists('offline_ticket_batches');
        Schema::dropIfExists('tourism_legal_waivers');
        Schema::dropIfExists('tourism_quotas');
        Schema::dropIfExists('tourism_destinations');
    }
};
