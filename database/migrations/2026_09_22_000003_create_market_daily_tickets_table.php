<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('market_daily_tickets')) {
            Schema::create('market_daily_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_code', 100)->unique();
                $table->unsignedBigInteger('collector_user_id')->nullable();
                $table->string('collector_name', 255)->nullable();
                $table->string('market_name', 255);
                $table->string('building_name', 255)->nullable();
                $table->string('merchant_name', 255)->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_amount', 15, 2)->default(1000);
                $table->decimal('total_amount', 15, 2)->default(1000);
                $table->string('payment_method', 50)->default('TUNAI');
                $table->string('qr_token', 100)->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();

                $table->index(['market_name', 'created_at']);
                $table->index(['collector_user_id', 'created_at']);
            });
        }

        // Auto-seed data master pasar jika belum pernah di-seed
        if (class_exists(\Database\Seeders\DisperindagMarketSeeder::class)) {
            try {
                if (\App\Models\Market::count() === 0) {
                    (new \Database\Seeders\DisperindagMarketSeeder())->run();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('DisperindagMarketSeeder auto-seed: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('market_daily_tickets');
    }
};
