<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('pc_number')->unique();       // PC-YYYY-######
            $table->string('full_name');
            $table->date('dob')->nullable();
            $table->string('nationality')->nullable();
            $table->string('emirate')->nullable();
            $table->string('marital_status')->nullable(); // single | married | widow
            $table->string('mobile1');
            $table->string('mobile2')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
