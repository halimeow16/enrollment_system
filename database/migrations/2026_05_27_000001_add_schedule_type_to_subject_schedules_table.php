<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->string('schedule_type', 10)->default('LEC')->after('instructor');
        });
    }

    public function down(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->dropColumn('schedule_type');
        });
    }
};
