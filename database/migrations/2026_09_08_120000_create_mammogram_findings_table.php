<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business feedback #5: the mammographer needs a dedicated form inside the
     * patient's file to record and submit the mammography findings — separate
     * from the uploaded report PDF, which may only arrive days later (#6).
     *
     * One row per patient history record.
     */
    public function up(): void
    {
        Schema::create('mammogram_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->unique()->constrained('patient_history_records')->cascadeOnDelete();
            $table->foreignId('mammographer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->date('exam_date')->nullable();
            $table->string('modality', 40)->nullable();          // mammogram | ultrasound | both
            $table->string('breast_density', 5)->nullable();     // a | b | c | d (ACR)

            // Per-side assessment: BI-RADS category + free-text findings.
            $table->string('birads_right', 5)->nullable();       // 0..6
            $table->string('birads_left', 5)->nullable();
            $table->text('findings_right')->nullable();
            $table->text('findings_left')->nullable();

            $table->text('comparison')->nullable();              // prior studies compared with
            $table->text('impression')->nullable();
            $table->string('recommendation', 60)->nullable();    // routine | short_interval | additional_imaging | biopsy | referral
            $table->text('notes')->nullable();

            $table->string('status', 20)->default('draft');      // draft | submitted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mammogram_findings');
    }
};
