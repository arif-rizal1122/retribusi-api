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
        Schema::create('bpn_h2h_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('nib', 50)->unique();
            $table->string('nop', 50)->nullable()->index();
            $table->decimal('znt_value', 15, 2)->default(0);
            $table->unsignedBigInteger('zona_id')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpn_h2h_mappings');
    }
};
