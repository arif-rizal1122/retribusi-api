<?php

namespace Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Lightweight in-memory schema builder used ONLY to run user-input (users store)
 * endpoint tests. The full application migration set currently fails on a fresh
 * SQLite database because of a conflicting `petugas_tasks` add-column migration,
 * so we only create the tables required by the UserController@store flow here.
 */
trait BuildsUserSchema
{
    protected function buildUserSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('opds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nik', 50)->unique()->nullable();
            $table->string('role')->default('citizen');
            $table->unsignedBigInteger('retribution_type_id')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('retribution_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('tariff_percent', 5, 2)->default(0);
            $table->string('icon')->nullable();
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->string('unit')->default('per_bulan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('retribution_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
            $table->foreignId('retribution_type_id')->constrained('retribution_types')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_retribution_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('retribution_type_id');
            $table->unsignedBigInteger('retribution_classification_id')->nullable();
            $table->foreign('retribution_type_id', 'ura_type_fk')->references('id')->on('retribution_types')->onDelete('cascade');
            $table->foreign('retribution_classification_id', 'ura_class_fk')->references('id')->on('retribution_classifications')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    protected function tearDownUserSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['audit_logs', 'user_retribution_assignments', 'retribution_classifications', 'retribution_types', 'users', 'opds'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }
}
