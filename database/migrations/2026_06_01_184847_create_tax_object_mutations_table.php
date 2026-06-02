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
        Schema::create('tax_object_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_object_id')->constrained('tax_objects')->cascadeOnDelete();
            $table->foreignId('old_taxpayer_id')->constrained('taxpayers');
            $table->foreignId('new_taxpayer_id')->constrained('taxpayers');
            $table->string('reason')->nullable();
            $table->foreignId('mutated_by')->constrained('users');
            $table->integer('voided_notices_count')->default(0);
            $table->integer('reassigned_bills_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_object_mutations');
    }
};
