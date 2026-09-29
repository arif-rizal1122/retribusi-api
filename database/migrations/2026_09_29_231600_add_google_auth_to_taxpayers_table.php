<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->string('avatar_url')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url']);
        });
    }
};
