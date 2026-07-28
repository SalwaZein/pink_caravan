<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->unique()->constrained('patient_history_records')->cascadeOnDelete();
            $table->string('path')->nullable();           // stored PDF path
            $table->timestamp('generated_at')->nullable();
            $table->json('delivery')->nullable();         // per-channel status: {email, sms, whatsapp, portal}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
