<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE enrollments MODIFY student_type VARCHAR(20) NOT NULL DEFAULT 'new'");
        DB::table('enrollments')->where('student_type', 'regular')->update(['student_type' => 'new']);
        DB::table('enrollments')->where('student_type', 'irregular')->update(['student_type' => 'old']);
        DB::statement("ALTER TABLE enrollments MODIFY student_type ENUM('new', 'old', 'transferee') NOT NULL DEFAULT 'new'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE enrollments MODIFY student_type VARCHAR(20) NOT NULL DEFAULT 'regular'");
        DB::table('enrollments')->where('student_type', 'new')->update(['student_type' => 'regular']);
        DB::table('enrollments')->whereIn('student_type', ['old', 'transferee'])->update(['student_type' => 'irregular']);
        DB::statement("ALTER TABLE enrollments MODIFY student_type ENUM('regular', 'irregular') NOT NULL DEFAULT 'regular'");
    }
};
