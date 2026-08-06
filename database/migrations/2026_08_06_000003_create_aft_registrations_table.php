<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aft_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('taxpayer_id');
            $table->string('status')->default('active'); // active | inactive
            $table->string('bank')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('beneficiary_account')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('approval_status')->default('pending'); // pending | approved | rejected
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('taxpayer_id')->references('id')->on('taxpayers')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['taxpayer_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aft_registrations');
    }
};
