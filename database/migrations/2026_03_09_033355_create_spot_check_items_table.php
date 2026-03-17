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
        Schema::create('spot_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spot_check_id')->constrained()->onDelete('cascade');
            $table->time('observation_time');
            $table->integer('visitor_count')->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spot_check_items');
    }
};
