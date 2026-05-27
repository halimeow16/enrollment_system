<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('enrollment_status');
            $table->string('archived_school_year')->nullable()->after('archived_at');

            $table->index(['school_year', 'archived_at']);
            $table->index('archived_school_year');
        });

        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->string('school_year')->nullable()->after('schedule_type');
            $table->timestamp('archived_at')->nullable()->after('school_year');
            $table->string('archived_school_year')->nullable()->after('archived_at');

            $table->index(['school_year', 'archived_at']);
            $table->index('archived_school_year');
        });
    }

    public function down(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->dropIndex(['school_year', 'archived_at']);
            $table->dropIndex(['archived_school_year']);
            $table->dropColumn(['school_year', 'archived_at', 'archived_school_year']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['school_year', 'archived_at']);
            $table->dropIndex(['archived_school_year']);
            $table->dropColumn(['archived_at', 'archived_school_year']);
        });
    }
};
