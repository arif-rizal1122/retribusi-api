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
        Schema::create('monthly_reports', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('taxpayer_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('tax_object_id')->constrained()->cascadeOnDelete();
            $blueprint->string('period'); // e.g., '2026-02'
            $blueprint->decimal('turnover_amount', 15, 2);
            $blueprint->decimal('tax_amount', 15, 2);
            $blueprint->json('attachments')->nullable();
            $blueprint->text('notes')->nullable();
            $blueprint->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $blueprint->timestamp('validated_at')->nullable();
            $blueprint->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $blueprint->timestamps();
            
            // Ensure unique report per object and period
            $blueprint->unique(['tax_object_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
