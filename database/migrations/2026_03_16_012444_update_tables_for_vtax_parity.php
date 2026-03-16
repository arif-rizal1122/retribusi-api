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
            $table->decimal('admin_fee', 15, 2)->default(0)->after('amount');
            $table->timestamp('postponed_at')->nullable()->after('status');
            $table->text('reason_postponed')->nullable()->after('postponed_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('tendered_amount', 15, 2)->nullable()->after('amount');
            $table->decimal('change_amount', 15, 2)->nullable()->after('tendered_amount');
        });

        Schema::table('enforcement_notices', function (Blueprint $table) {
            $table->foreignId('bill_id')->nullable()->constrained('bills')->onDelete('set null')->after('tax_object_id');
            $table->decimal('amount_at_issue', 15, 2)->nullable()->after('bill_id');
            $table->timestamp('rejected_at')->nullable()->after('status');
            $table->text('rejection_notes')->nullable()->after('rejected_at');
        });

        Schema::table('verifications', function (Blueprint $table) {
            $table->boolean('is_assessment_final')->default(false)->after('status');
            $table->timestamp('assessed_at')->nullable()->after('is_assessment_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn(['is_assessment_final', 'assessed_at']);
        });

        Schema::table('enforcement_notices', function (Blueprint $table) {
            $table->dropForeign(['bill_id']);
            $table->dropColumn(['bill_id', 'amount_at_issue', 'rejected_at', 'rejection_notes']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['tendered_amount', 'change_amount']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['admin_fee', 'postponed_at', 'reason_postponed']);
        });
    }
};
