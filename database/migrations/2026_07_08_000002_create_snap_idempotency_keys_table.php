<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snap_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('endpoint');
            $table->string('request_hash')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('status', 30)->default('processing');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['endpoint', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('snap_idempotency_keys');
    }
};
