<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Emirates ID number (784-YYYY-NNNNNNN-C), optionally read from the card.
            $table->string('emirates_id')->nullable()->after('manual_pc_number');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('emirates_id');
        });
    }
};
