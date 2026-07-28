<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();           // PCB-YYYY-####
            $table->string('organisation');
            $table->string('contact_person');
            $table->string('email');
            $table->string('mobile');
            $table->string('emirate')->nullable();
            $table->unsignedInteger('estimated_participants')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('new');     // new | contacted | scheduled | done
            $table->timestamps();
        });

        Schema::create('booking_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('service_type');               // A | B | C | D
            $table->string('selection')->nullable();
            $table->boolean('add_team')->default(false);
            $table->date('event_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('venue')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_service_requests');
        Schema::dropIfExists('bookings');
    }
};
