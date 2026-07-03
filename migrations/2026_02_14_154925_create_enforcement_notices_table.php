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
        Schema::create('enforcement_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_object_id')->constrained()->onDelete('cascade');
            $table->string('type'); // teguran_1, teguran_2, paksa, penyitaan
            $table->string('number')->unique();
            $table->string('status')->default('draft'); // draft, sent, approved
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enforcement_notices');
    }
};
