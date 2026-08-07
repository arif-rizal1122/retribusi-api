<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log konektor H2H (SIMPBB, Dukcapil, BHUMI) untuk monitoring & notifikasi.
     */
    public function up(): void
    {
        Schema::create('h2h_connector_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('connector', ['simpbb', 'dukcapil', 'bhumi']);
            $table->enum('status', ['ok', 'failed', 'fallback', 'not_configured']);
            $table->string('endpoint')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['connector', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('h2h_connector_logs');
    }
};
