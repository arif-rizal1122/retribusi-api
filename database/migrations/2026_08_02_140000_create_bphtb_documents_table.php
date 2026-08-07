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
        Schema::create('bphtb_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bphtb_submission_id');
            $table->string('document_type', 50)->default('other'); // ajb, ktp, npwp, sppt, akta, sk, other
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('bphtb_submission_id')
                ->references('id')
                ->on('bphtb_submissions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bphtb_documents');
    }
};
