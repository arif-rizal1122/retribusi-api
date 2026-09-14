<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sanksi hasil rekonsiliasi (Sidak fisik vs digital): SP1/SP2/SP3.
     * Hanya role pengawas/opd/super_admin yang boleh mencatat (dijaga di controller).
     */
    public function up(): void
    {
        Schema::create('parking_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jukir_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('inspector_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parking_location_id')->nullable()->constrained('parking_locations')->nullOnDelete();
            $table->string('sanction_type'); // sp1_warning | sp2_freeze_7days | sp3_revoke_st
            $table->text('reason')->nullable();
            $table->string('status')->default('applied'); // applied | revoked
            $table->timestamps();

            $table->index(['jukir_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_sanctions');
    }
};