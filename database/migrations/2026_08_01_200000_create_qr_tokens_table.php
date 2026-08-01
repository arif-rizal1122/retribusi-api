<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('qr_tokens')) {
            Schema::create('qr_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('model_type');          // App\Models\Taxpayer | App\Models\User
                $table->unsignedBigInteger('model_id');
                $table->string('token', 64)->unique(); // kode acak QR
                $table->timestamps();

                $table->index(['model_type', 'model_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('qr_tokens');
    }
};
