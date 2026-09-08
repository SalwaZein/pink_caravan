<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business feedback #7 — WhatsApp token & queue management.
     *
     * A volunteer at the clinic door enters the visitor's name + WhatsApp number;
     * the system issues the next token for THAT clinic (the location) on THAT day
     * and sends it over WhatsApp. Numbering is per clinic per day, so two mobile
     * clinics running at once each start at #1.
     */
    public function up(): void
    {
        Schema::create('queue_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();   // the location
            $table->date('queue_date');
            $table->unsignedInteger('number');                                  // order of arrival, per clinic per day
            $table->string('code', 30);                                         // display token, e.g. "DXB-MOB-01-014"

            $table->string('patient_name');
            $table->string('whatsapp_number', 40);
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();

            // waiting → notified (your turn is approaching) → called → in_clinic → served
            // plus the exits: no_show, cancelled.
            $table->string('status', 20)->default('waiting');

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('notified_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->json('delivery')->nullable();                                // per-message WhatsApp status
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'queue_date', 'number']);
            $table->index(['clinic_id', 'queue_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tokens');
    }
};
