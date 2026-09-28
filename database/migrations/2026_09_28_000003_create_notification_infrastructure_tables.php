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
        // 1. NOTIFICATION TEMPLATES
        if (!Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('group', 20)->comment('warga, petugas, admin');
                $table->string('label');
                $table->json('channels')->comment('["whatsapp", "push", "in_app"]');
                $table->string('priority', 10)->default('medium')->comment('high, medium, low');
                $table->text('wa_template')->nullable();
                $table->string('push_title')->nullable();
                $table->text('push_body')->nullable();
                $table->string('deep_link_pattern')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('group');
                $table->index('is_active');
            });
        }

        // 2. USER DEVICE TOKENS (Push notifications VAPID / FCM)
        if (!Schema::hasTable('user_device_tokens')) {
            Schema::create('user_device_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('platform', 20)->default('web')->comment('web, android, ios');
                $table->string('app', 20)->default('mobile')->comment('mobile, petugas, admin');
                $table->text('token');
                $table->json('keys')->nullable()->comment('VAPID p256dh and auth keys');
                $table->timestamp('last_active_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index(['app', 'platform']);
            });
        }

        // 3. UPGRADE NOTIFICATION LOGS
        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('notification_logs', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('taxpayer_id');
                    $table->index('user_id');
                }
                if (!Schema::hasColumn('notification_logs', 'template_code')) {
                    $table->string('template_code', 50)->nullable()->after('type');
                    $table->index('template_code');
                }
                if (!Schema::hasColumn('notification_logs', 'idempotency_key')) {
                    $table->string('idempotency_key', 64)->nullable()->unique()->after('error_message');
                }
                if (!Schema::hasColumn('notification_logs', 'gateway_message_id')) {
                    $table->string('gateway_message_id')->nullable()->after('idempotency_key');
                }
                if (!Schema::hasColumn('notification_logs', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable()->after('sent_at');
                }
                if (!Schema::hasColumn('notification_logs', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('delivered_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $columns = ['read_at', 'delivered_at', 'gateway_message_id', 'idempotency_key', 'template_code', 'user_id'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('notification_logs', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('user_device_tokens');
        Schema::dropIfExists('notification_templates');
    }
};
