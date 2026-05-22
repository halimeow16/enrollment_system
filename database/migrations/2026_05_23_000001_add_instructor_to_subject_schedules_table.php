<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->string('instructor')->nullable()->after('room_id');
            $table->index(['day_id', 'instructor'], 'subject_schedules_day_instructor_index');
        });
    }

    public function down(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->dropIndex('subject_schedules_day_instructor_index');
            $table->dropColumn('instructor');
        });
    }
};
