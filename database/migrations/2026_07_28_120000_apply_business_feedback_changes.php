<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business-feedback changes (July 2026) from the Doctor + Medical form reviews.
 *  - Registration form: manual "PC Number" (mammographer-entered), breast-implant flag,
 *    per-item personal-history notes, patient signature + date.
 *  - Mammographer role assignment on each record.
 *  - Post-campaign report workflow: uploaded mammogram report + sent tracking.
 *  - Doctor exam: explicit Clinical Breast Examination result (normal/abnormal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // "Registration Number" stays the auto pc_number; this is the manual PC Number.
            $table->string('manual_pc_number')->nullable()->after('pc_number');
        });

        Schema::table('patient_history_records', function (Blueprint $table) {
            // Section 1 — replaces "Number of Children" (kept nullable for old rows).
            $table->string('breast_implant')->nullable()->after('number_of_children'); // yes | no
            // Section 2 — conditional comment per "Yes" answer: { item: "note" }.
            $table->json('personal_history_notes')->nullable()->after('personal_history');
            // Assigned mammographer (name shown alongside doctor & nurse on the record).
            $table->foreignId('mammographer_id')->nullable()->after('assigned_doctor_id')
                ->constrained('users')->nullOnDelete();
            // Section 5 — patient signature (data URL) + date of signature.
            $table->longText('patient_signature')->nullable()->after('consent_statements');
            $table->date('signed_at')->nullable()->after('patient_signature');
            // Post-campaign mammogram report handling.
            $table->string('mammogram_report_path')->nullable()->after('signed_at');
            $table->timestamp('report_uploaded_at')->nullable()->after('mammogram_report_path');
            $table->timestamp('report_sent_at')->nullable()->after('report_uploaded_at');
        });

        Schema::table('clinical_examinations', function (Blueprint $table) {
            // Doctor's Clinical Breast Examination result, chosen after the breast diagram.
            $table->string('cbe_result')->nullable()->after('pins'); // normal | abnormal
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('manual_pc_number');
        });

        Schema::table('patient_history_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mammographer_id');
            $table->dropColumn([
                'breast_implant', 'personal_history_notes', 'patient_signature', 'signed_at',
                'mammogram_report_path', 'report_uploaded_at', 'report_sent_at',
            ]);
        });

        Schema::table('clinical_examinations', function (Blueprint $table) {
            $table->dropColumn('cbe_result');
        });
    }
};
