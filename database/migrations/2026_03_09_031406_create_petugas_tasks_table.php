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
        if (Schema::hasTable('petugas_tasks')) {
            // Pastikan kolom ada pada database yang tabelnya sudah dibuat manual.
            Schema::table('petugas_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('petugas_tasks', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                }
            });

            return;
        }

        Schema::create('petugas_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->foreignId('taxpayer_id')->nullable()->constrained('taxpayers')->onDelete('set null');
            $table->foreignId('tax_object_id')->nullable()->constrained('tax_objects')->onDelete('set null');
            $table->foreignId('verification_id')->nullable()->constrained('verifications')->onDelete('set null');
            $table->string('task_type');
            $table->string('status')->default('pending');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('completion_photo_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('petugas_tasks')) {
            Schema::table('petugas_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('petugas_tasks', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                }
            });
        }
    }
};
