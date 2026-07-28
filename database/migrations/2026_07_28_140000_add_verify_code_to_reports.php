<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Public verification code printed on the report (format V-XXXX-XXXX).
            $table->string('verify_code')->nullable()->unique()->after('record_id');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['verify_code']);
            $table->dropColumn('verify_code');
        });
    }
};
