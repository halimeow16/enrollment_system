<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'student_type')) {
                return;
            }

            $table->enum('student_type', ['new', 'old', 'transferee'])
                ->default('new')
                ->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'student_type')) {
                return;
            }

            $table->dropColumn('student_type');
        });
    }
};
