<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('h2h_monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('connector', 50)->index();
            $table->string('status', 20); // ok / slow / down / not_configured
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->string('endpoint', 255)->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('h2h_monitor_logs');
    }
};
