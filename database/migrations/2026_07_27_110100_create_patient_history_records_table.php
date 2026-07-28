<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_history_records', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();          // mirrors patient PC number for the visit
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('record_date')->nullable();

            // Reproductive history
            $table->unsignedInteger('age_at_menarche')->nullable();
            $table->unsignedInteger('number_of_children')->nullable();
            $table->unsignedInteger('age_first_delivery')->nullable();
            $table->date('lmp')->nullable();
            $table->boolean('menopause')->default(false);
            $table->unsignedSmallInteger('menopause_since_year')->nullable();

            // Grouped sections stored as JSON (yes/no items + conditional details, relatives)
            $table->json('personal_history')->nullable();
            $table->json('family_history')->nullable();

            // Screening & result
            $table->boolean('breastfeeding_under6')->default(false);
            $table->unsignedInteger('breastfeeding_children')->nullable();
            $table->date('last_mammogram')->nullable();
            $table->string('cbe_result')->nullable();     // normal | abnormal (nurse's initial)
            $table->string('examiner_name')->nullable();

            // Consent
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->json('consent_statements')->nullable();

            // Workflow
            $table->string('status')->default('draft');   // draft|submitted|assigned|in_review|completed
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_history_records');
    }
};
