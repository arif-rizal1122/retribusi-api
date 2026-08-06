<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aft_registrations', function (Blueprint $table) {
            $table->string('surat_kuasa_url')->nullable()->after('beneficiary_name');
            $table->timestamp('submitted_at')->nullable()->after('surat_kuasa_url');
            $table->string('rejection_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('aft_registrations', function (Blueprint $table) {
            $table->dropColumn(['surat_kuasa_url', 'submitted_at', 'rejection_reason']);
        });
    }
};
