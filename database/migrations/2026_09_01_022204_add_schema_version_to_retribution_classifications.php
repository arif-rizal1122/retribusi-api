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
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->integer('schema_version')->default(1)->after('form_schema')->comment('Versi struktur form untuk data retention.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retribution_classifications', function (Blueprint $table) {
            $table->dropColumn('schema_version');
        });
    }
};
