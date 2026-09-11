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
        Schema::create('dlh_lab_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('method')->nullable();
            $table->string('unit', 50)->default('mg/L');
            $table->decimal('tariff', 15, 2)->default(0);
            $table->string('standard_reference')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dlh_lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('taxpayer_id')->nullable()->constrained('taxpayers')->nullOnDelete();
            $table->string('applicant_name');
            $table->string('institution_name')->nullable();
            $table->string('contact_phone', 50);
            $table->string('applicant_address')->nullable();
            $table->string('sample_matrix', 50)->default('AIR_LIMBAH'); // AIR_LIMBAH, AIR_SUNGAI, AIR_BERSIH, AIR_LAUT
            $table->string('sampling_location')->nullable();
            $table->date('sampling_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
            $table->enum('status', [
                'DRAFT',
                'BILLING_ISSUED',
                'PAID',
                'SAMPLING',
                'ANALYZING',
                'COMPLETED',
                'CANCELLED'
            ])->default('DRAFT');
            $table->string('lhu_document_url')->nullable();
            $table->string('tte_status', 50)->default('NOT_SIGNED'); // NOT_SIGNED, PENDING, SIGNED, REJECTED
            $table->string('tte_signature_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('dlh_lab_order_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('dlh_lab_orders')->cascadeOnDelete();
            $table->foreignId('parameter_id')->constrained('dlh_lab_parameters')->cascadeOnDelete();
            $table->decimal('tariff_snapshot', 15, 2);
            $table->string('result_value')->nullable();
            $table->string('quality_standard_value')->nullable();
            $table->enum('compliance_status', ['COMPLIANT', 'EXCEEDED', 'PENDING'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dlh_lab_order_parameters');
        Schema::dropIfExists('dlh_lab_orders');
        Schema::dropIfExists('dlh_lab_parameters');
    }
};
