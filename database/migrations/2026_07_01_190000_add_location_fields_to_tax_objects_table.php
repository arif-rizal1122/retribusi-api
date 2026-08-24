<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_objects', function (Blueprint $table) {
            $table->string('district')->nullable()->after('address');
            $table->string('sub_district')->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('tax_objects', function (Blueprint $table) {
            $table->dropColumn(['district', 'sub_district']);
        });
    }
};
