<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_id')->constrained('patient_history_records')->cascadeOnDelete();
            $table->string('type');                       // mammogram | uls
            $table->string('hospital')->nullable();
            $table->date('referral_date')->nullable();
            $table->string('status')->default('pending'); // pending | completed
            $table->string('result')->nullable();
            $table->date('result_date')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
