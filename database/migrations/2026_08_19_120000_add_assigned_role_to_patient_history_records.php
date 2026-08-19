<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which role a case is currently assigned to ('doctor' | 'mammographer'),
 * so the clinic admin can route a case to either role and know who is acting on it.
 * The assignee id itself still lives in assigned_doctor_id / mammographer_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_history_records', function (Blueprint $table) {
            $table->string('assigned_role')->nullable()->after('mammographer_id'); // doctor|mammographer
        });
    }

    public function down(): void
    {
        Schema::table('patient_history_records', function (Blueprint $table) {
            $table->dropColumn('assigned_role');
        });
    }
};
