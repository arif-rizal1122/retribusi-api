<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rating/umpan balik citizen terhadap layanan M-PAD
     * (kinerja aplikasi, kanal bank, petugas OPD).
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxpayer_id')->nullable()->constrained('taxpayers')->onDelete('cascade');
            $table->string('category');
            $table->string('sub_item');
            $table->tinyInteger('score');
            $table->string('issue')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['taxpayer_id', 'category']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
