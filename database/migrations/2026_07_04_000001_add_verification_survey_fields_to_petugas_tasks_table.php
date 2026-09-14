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
        Schema::table('petugas_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('petugas_tasks', 'tax_object_id')) {
                $table->foreignId('tax_object_id')->nullable()->after('taxpayer_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('petugas_tasks', 'verification_id')) {
                $table->foreignId('verification_id')->nullable()->after('tax_object_id')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petugas_tasks', function (Blueprint $table) {
            $table->dropForeign(['tax_object_id']);
            $table->dropForeign(['verification_id']);
            $table->dropColumn(['task_type', 'tax_object_id', 'verification_id']);
        });
    }
};
