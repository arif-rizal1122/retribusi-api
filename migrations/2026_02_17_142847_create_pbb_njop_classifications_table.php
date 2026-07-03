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
        Schema::create('pbb_njop_classifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bumi', 'bangunan']);
            $table->string('class_code');
            $table->decimal('min_value', 15, 2);
            $table->decimal('max_value', 15, 2)->nullable();
            $table->decimal('njop_value', 15, 2);
            $table->timestamps();

            $table->unique(['type', 'class_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbb_njop_classifications');
    }
};
