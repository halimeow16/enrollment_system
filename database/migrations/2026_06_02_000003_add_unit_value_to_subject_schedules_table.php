<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->unsignedTinyInteger('unit_value')->nullable()->after('schedule_for');
        });
    }

    public function down(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->dropColumn('unit_value');
        });
    }
};
