<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        DB::statement("
            ALTER TABLE department_heads
            ADD COLUMN active_course_code VARCHAR(255)
                GENERATED ALWAYS AS (
                    IF(is_active = 1, course_code, NULL)
                ) VIRTUAL
        ");

        DB::statement("
            ALTER TABLE department_heads
            ADD UNIQUE INDEX unique_active_dept_head_per_course (active_course_code)
        ");
    }

    public function down(): void
    {
        Schema::table('department_heads', function ($table) {
            $table->dropUnique('unique_active_dept_head_per_course');
        });

        DB::statement("
            ALTER TABLE department_heads
            DROP COLUMN active_course_code
        ");
    }
};