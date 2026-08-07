<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->string('nib', 16)->nullable()->after('npwpd');
        });
    }

    public function down(): void
    {
        Schema::table('taxpayers', function (Blueprint $table) {
            $table->dropColumn('nib');
        });
    }
};
