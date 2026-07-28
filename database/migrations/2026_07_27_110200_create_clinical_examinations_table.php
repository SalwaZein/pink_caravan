<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->unique()->constrained('patient_history_records')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('exam_date')->nullable();
            $table->foreignId('venue_clinic_id')->nullable()->constrained('clinics')->nullOnDelete();

            // Findings — {index: {R:bool, L:bool}}
            $table->json('symptoms')->nullable();
            $table->string('other_symptoms')->nullable();
            $table->json('signs')->nullable();
            $table->string('skin_rash_discharge_type')->nullable(); // skin_duct | multiple_duct
            $table->string('other_signs')->nullable();

            // Breast diagram pins — [{x, y}] percentages
            $table->json('pins')->nullable();

            $table->text('comments')->nullable();
            $table->text('other_findings')->nullable();
            $table->string('recommendation')->nullable();
            $table->string('result')->nullable();          // normal | abnormal

            // Attestation
            $table->string('examiner_name')->nullable();
            $table->timestamp('attested_at')->nullable();

            $table->string('status')->default('draft');    // draft | submitted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_examinations');
    }
};
