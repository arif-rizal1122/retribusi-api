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
        Schema::create('dlh_ingest_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source_file');
            $table->unsignedInteger('row_index')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('status', 50)->default('PENDING'); // SUCCESS, QUARANTINE_MISSING_COORDS, QUARANTINE_DUPLICATE_ID, ERROR
            $table->text('anomaly_reason')->nullable();
            $table->foreignId('tax_object_id')->nullable()->constrained('tax_objects')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['source_file', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dlh_ingest_logs');
    }
};
